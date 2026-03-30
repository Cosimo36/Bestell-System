import React from 'react';
import { motion } from 'framer-motion';
interface OrderTotalProps {
  subtotal: number;
  taxRate?: number; // e.g., 0.19 for 19%
}
const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('de-DE', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount);
};
export function OrderTotal({ subtotal, taxRate = 0.19 }: OrderTotalProps) {
  // In Germany, prices usually already include tax.
  // We just show the total and a note that it includes VAT.
  const total = subtotal;
  const includedTax = total - total / (1 + taxRate);
  return (
    <motion.div
      initial={{
        opacity: 0,
        y: 20
      }}
      animate={{
        opacity: 1,
        y: 0
      }}
      transition={{
        delay: 0.5,
        duration: 0.5
      }}
      className="mt-8 pt-6 border-t border-gastro-border">
      
      <div className="flex justify-between items-center mb-2 text-gastro-muted">
        <span>Zwischensumme</span>
        <span>{formatCurrency(subtotal)}</span>
      </div>
      <div className="flex justify-between items-end mt-4">
        <div>
          <h3 className="text-2xl font-bold text-gastro-text">Gesamt</h3>
          <p className="text-xs text-gastro-muted mt-1">
            Inkl. {taxRate * 100}% MwSt. ({formatCurrency(includedTax)})
          </p>
        </div>
        <div className="text-3xl font-bold text-gastro-accent">
          {formatCurrency(total)}
        </div>
      </div>
    </motion.div>);

}