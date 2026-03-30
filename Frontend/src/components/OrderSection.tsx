import React from 'react';
import { motion } from 'framer-motion';
import { BoxIcon } from 'lucide-react';
interface OrderSectionProps {
  title: string;
  icon: BoxIcon;
  children: React.ReactNode;
}
export function OrderSection({
  title,
  icon: Icon,
  children
}: OrderSectionProps) {
  return (
    <section className="mb-8">
      <div className="flex items-center gap-2 mb-2 pb-2 border-b-2 border-gastro-text/10">
        <Icon className="w-5 h-5 text-gastro-accent" strokeWidth={2} />
        <h2 className="text-xl font-semibold text-gastro-text tracking-wide">
          {title}
        </h2>
      </div>
      <div className="flex flex-col">{children}</div>
    </section>);

}