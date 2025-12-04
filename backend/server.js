// backend/server.js
const express = require("express");
const cors = require("cors");
const mongoose = require("mongoose");
const dotenv = require("dotenv");
const Razorpay = require("razorpay");
const crypto = require("crypto");
const Enquiry = require("./models/Enquiry");
const Payment = require("./models/Payment");

dotenv.config();

const app = express();

// ====== CONFIG ======
const PORT = process.env.PORT || 5000;
const MONGODB_URI =
  process.env.MONGODB_URI ||
  "mongodb://127.0.0.1:27017/shubham-dev-studio";

const FRONTEND_ORIGIN =
  process.env.FRONTEND_ORIGIN || "http://localhost:5500"; // VS Code live server / file server
// Deploy केल्यावर इथे तुझा Netlify URL टाकू शकतोस

// ====== MIDDLEWARE ======
app.use(
  cors({
    origin: (origin, callback) => {
      // local file / Postman साठी
      if (!origin) return callback(null, true);
      return callback(null, true);
    },
    credentials: true,
  })
);

app.use(express.json());

// ====== MONGODB CONNECT ======
mongoose
  .connect(MONGODB_URI)
  .then(() => console.log("✅ MongoDB connected"))
  .catch((err) => {
    console.error("❌ MongoDB connection error:", err.message);
  });

// ====== RAZORPAY INSTANCE ======
const razorpayKeyId = process.env.RAZORPAY_KEY_ID;
const razorpayKeySecret = process.env.RAZORPAY_KEY_SECRET;

let razorpay = null;
if (razorpayKeyId && razorpayKeySecret) {
  razorpay = new Razorpay({
    key_id: razorpayKeyId,
    key_secret: razorpayKeySecret,
  });
  console.log("✅ Razorpay configured");
} else {
  console.log(
    "⚠️ Razorpay keys missing in .env (RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET)"
  );
}

// ====== ROUTES ======

// Health check
app.get("/", (req, res) => {
  res.json({ message: "Shubham Dev Studio backend running 🚀" });
});

// 📩 Contact form → save enquiry
app.post("/api/contact", async (req, res) => {
  try {
    const { name, contact, need, message } = req.body;

    if (!name || !contact || !need) {
      return res
        .status(400)
        .json({ success: false, message: "Required fields missing" });
    }

    const enquiry = new Enquiry({ name, contact, need, message });
    await enquiry.save();

    return res.json({ success: true, enquiryId: enquiry._id });
  } catch (err) {
    console.error("Contact error:", err);
    return res
      .status(500)
      .json({ success: false, message: "Server error while saving enquiry" });
  }
});

// 💰 Create Razorpay order
app.post("/api/payment/create-order", async (req, res) => {
  try {
    if (!razorpay) {
      return res.status(500).json({
        success: false,
        message: "Razorpay is not configured yet.",
      });
    }

    const { amount, planName } = req.body;
    if (!amount || amount <= 0) {
      return res
        .status(400)
        .json({ success: false, message: "Invalid amount" });
    }

    const amountInPaise = Math.round(amount * 100);

    const order = await razorpay.orders.create({
      amount: amountInPaise,
      currency: "INR",
      notes: {
        planName: planName || "Unknown plan",
      },
    });

    // Payment document (status = created)
    const payment = new Payment({
      planName: planName || "Unknown plan",
      amount,
      currency: "INR",
      status: "created",
      razorpayOrderId: order.id,
    });
    await payment.save();

    return res.json({
      success: true,
      key: razorpayKeyId,
      order,
    });
  } catch (err) {
    console.error("Create order error:", err);
    return res.status(500).json({
      success: false,
      message: "Server error while creating Razorpay order",
    });
  }
});

// ✅ Verify Razorpay payment signature
app.post("/api/payment/verify", async (req, res) => {
  try {
    const {
      razorpay_order_id,
      razorpay_payment_id,
      razorpay_signature,
      amount,
      planName,
    } = req.body;

    if (
      !razorpay_order_id ||
      !razorpay_payment_id ||
      !razorpay_signature
    ) {
      return res
        .status(400)
        .json({ success: false, message: "Invalid razorpay payload" });
    }

    const body = razorpay_order_id + "|" + razorpay_payment_id;
    const expectedSignature = crypto
      .createHmac("sha256", razorpayKeySecret)
      .update(body.toString())
      .digest("hex");

    const isValid = expectedSignature === razorpay_signature;

    // DB मधला payment update कर
    const payment = await Payment.findOne({
      razorpayOrderId: razorpay_order_id,
    });

    if (payment) {
      payment.status = isValid ? "success" : "failed";
      payment.razorpayPaymentId = razorpay_payment_id;
      payment.razorpaySignature = razorpay_signature;
      payment.rawResponse = {
        razorpay_order_id,
        razorpay_payment_id,
        razorpay_signature,
        amount,
        planName,
      };
      await payment.save();
    }

    if (!isValid) {
      return res.status(400).json({
        success: false,
        message: "Payment signature verification failed",
      });
    }

    return res.json({ success: true });
  } catch (err) {
    console.error("Verify error:", err);
    return res.status(500).json({
      success: false,
      message: "Server error while verifying payment",
    });
  }
});

// ====== START SERVER ======
app.listen(PORT, () => {
  console.log(`✅ Server running on http://localhost:${PORT}`);
});
