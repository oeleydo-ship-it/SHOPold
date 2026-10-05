import {
    BadgeCheck, Clock, CreditCard, Flame, Gift, Headset, Heart, RotateCcw, ShieldCheck, Sparkles, Star, Tag, Truck,
} from 'lucide-react';

/** Homepage settings store Font Awesome icon names (feature_1_icon...); these are their lucide equivalents. */
const ICONS = {
    star: Star, fire: Flame, sparkles: Sparkles, gift: Gift, tag: Tag, truck: Truck, undo: RotateCcw,
    'shield-alt': ShieldCheck, headset: Headset, clock: Clock, 'credit-card': CreditCard, heart: Heart,
};

export const settingIcon = (name) => ICONS[name] ?? BadgeCheck;
