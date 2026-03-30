import React, { useState, Children } from 'react';
import { motion } from 'framer-motion';
import { Coffee, Utensils, BellRing, Clock } from 'lucide-react';
import { OrderItemCard, OrderItemProps } from '../components/OrderItemCard';
import { OrderSection } from '../components/OrderSection';
import { OrderTotal } from '../components/OrderTotal';
import { EmptyState } from '../components/EmptyState';
// --- MOCK DATA ---
const MOCK_DRINKS: OrderItemProps[] = [
{
  id: 'd1',
  name: 'Weißbier',
  quantity: 2,
  price: 4.2,
  timestamp: '18:15'
},
{
  id: 'd2',
  name: 'Apfelschorle',
  quantity: 1,
  price: 3.5,
  timestamp: '18:15'
},
{
  id: 'd3',
  name: 'Aperol Spritz',
  quantity: 1,
  price: 8.5,
  timestamp: '18:45'
},
{
  id: 'd4',
  name: 'Espresso',
  quantity: 1,
  price: 2.8,
  timestamp: '19:45'
}];

const MOCK_FOOD: OrderItemProps[] = [
{
  id: 'f1',
  name: 'Wiener Schnitzel mit Pommes',
  quantity: 1,
  price: 16.9,
  timestamp: '18:32'
},
{
  id: 'f2',
  name: 'Caesar Salad',
  quantity: 1,
  price: 12.5,
  timestamp: '18:32'
},
{
  id: 'f3',
  name: 'Käsespätzle',
  quantity: 1,
  price: 13.8,
  timestamp: '18:32'
}];

const containerVariants = {
  hidden: {
    opacity: 0
  },
  visible: {
    opacity: 1,
    transition: {
      staggerChildren: 0.1
    }
  }
};
export function OrderSummaryPage() {
  // Toggle this to test the empty state
  const [isEmpty] = useState(false);
  const drinksTotal = MOCK_DRINKS.reduce(
    (acc, item) => acc + item.price * item.quantity,
    0
  );
  const foodTotal = MOCK_FOOD.reduce(
    (acc, item) => acc + item.price * item.quantity,
    0
  );
  const subtotal = drinksTotal + foodTotal;
  const now = new Date();
  const timeString = now.toLocaleTimeString('de-DE', {
    hour: '2-digit',
    minute: '2-digit'
  });
  const dateString = now.toLocaleDateString('de-DE', {
    weekday: 'short',
    day: '2-digit',
    month: 'short'
  });
  return (
    <div className="min-h-screen bg-gastro-bg flex flex-col items-center pb-24">
      <div className="w-full max-w-md bg-gastro-bg min-h-screen shadow-soft sm:border-x sm:border-gastro-border relative">
        {/* Header */}
        <header className="sticky top-0 z-10 bg-gastro-bg/90 backdrop-blur-md border-b border-gastro-border px-6 py-5">
          <div className="flex justify-between items-start mb-1">
            <h1 className="text-2xl font-bold text-gastro-text">
              Gasthaus zum Löwen
            </h1>
            <div className="bg-gastro-surface border border-gastro-border px-3 py-1 rounded-full text-sm font-medium text-gastro-accent shadow-sm">
              Tisch 7
            </div>
          </div>
          <div className="flex items-center text-sm text-gastro-muted gap-1.5">
            <Clock className="w-3.5 h-3.5" />
            <span>
              {dateString}, {timeString} Uhr
            </span>
          </div>
        </header>

        {/* Main Content */}
        <main className="px-6 py-8">
          {isEmpty ?
          <EmptyState /> :

          <motion.div
            variants={containerVariants}
            initial="hidden"
            animate="visible">
            
              {MOCK_DRINKS.length > 0 &&
            <OrderSection title="Getränke" icon={Coffee}>
                  {MOCK_DRINKS.map((item) =>
              <OrderItemCard key={item.id} {...item} />
              )}
                </OrderSection>
            }

              {MOCK_FOOD.length > 0 &&
            <OrderSection title="Speisen" icon={Utensils}>
                  {MOCK_FOOD.map((item) =>
              <OrderItemCard key={item.id} {...item} />
              )}
                </OrderSection>
            }

              <OrderTotal subtotal={subtotal} />
            </motion.div>
          }
        </main>

        {/* Fixed Bottom Action */}
        <div className="fixed bottom-0 left-0 right-0 sm:absolute sm:bottom-0 p-6 bg-gradient-to-t from-gastro-bg via-gastro-bg to-transparent pointer-events-none flex justify-center">
          <motion.button
            whileHover={{
              scale: 1.02
            }}
            whileTap={{
              scale: 0.98
            }}
            className="pointer-events-auto flex items-center justify-center gap-2 w-full max-w-[calc(100%-3rem)] bg-gastro-text text-white py-4 px-6 rounded-2xl font-medium shadow-lg hover:bg-black transition-colors group">
            
            <BellRing className="w-5 h-5 group-hover:animate-pulse text-gastro-accent" />
            <span>Kellner rufen</span>
          </motion.button>
        </div>
      </div>
    </div>);

}