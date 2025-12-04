const mongoose = require("mongoose");

const paymentSchema = new mongoose.Schema(
  {
    planName: { type: String },
    amount: { type: Number, required: true }, // in rupees
    razorpay_order_id: { type: String, required: true },
    razorpay_payment_id: { type: String, required: true },
    razorpay_signature: { type: String, required: true },
    status: { type: String, default: "SUCCESS" }
  },
  { timestamps: true }
);

module.exports = mongoose.model("Payment", paymentSchema);
