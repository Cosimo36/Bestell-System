import React from 'react';
import { motion } from 'framer-motion';
import { UtensilsCrossed } from 'lucide-react';
export function EmptyState() {
  return (
    <motion.div
      initial={{
        opacity: 0,
        scale: 0.95
      }}
      animate={{
        opacity: 1,
        scale: 1
      }}
      transition={{
        duration: 0.5
      }}
      className="flex flex-col items-center justify-center py-20 px-6 text-center">
      
      <div className="w-20 h-20 bg-gastro-surface rounded-full shadow-soft flex items-center justify-center mb-6 text-gastro-accent/50">
        <UtensilsCrossed className="w-10 h-10" strokeWidth={1.5} />
      </div>
      <h3 className="text-2xl font-serif font-semibold text-gastro-text mb-2">
        Noch keine Bestellungen
      </h3>
      <p className="text-gastro-muted max-w-[250px]">
        Ihre bestellten Speisen und Getränke werden hier angezeigt.
      </p>
    </motion.div>);

}