import React from 'react';
import { motion } from 'framer-motion';
export interface OrderItemProps {
  id: string;
  name: string;
  quantity: number;
  price: number;
  timestamp: string;
}
const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('de-DE', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount);
};
const itemVariants = {
  hidden: {
    opacity: 0,
    y: 10
  },
  visible: {
    opacity: 1,
    y: 0,
    transition: {
      duration: 0.4,
      ease: 'easeOut'
    }
  }
};
export function OrderItemCard({
  name,
  quantity,
  price,
  timestamp
}: OrderItemProps) {
  const lineTotal = quantity * price;
  return (
    <motion.div
      variants={itemVariants}
      className="flex flex-col py-4 border-b border-gastro-border last:border-0">
      
      <div className="flex justify-between items-start mb-1">
        <div className="flex items-start gap-3">
          <span className="font-medium text-gastro-accent min-w-[24px]">
            {quantity}x
          </span>
          <div>
            <h4 className="font-medium text-gastro-text text-base leading-tight">
              {name}
            </h4>
            <div className="flex items-center gap-2 mt-1 text-sm text-gastro-muted">
              <span>{timestamp}</span>
              <span className="w-1 h-1 rounded-full bg-gastro-border"></span>
              <span>à {formatCurrency(price)}</span>
            </div>
          </div>
        </div>
        <div className="font-medium text-gastro-text ml-4">
          {formatCurrency(lineTotal)}
        </div>
      </div>
    </motion.div>);

}