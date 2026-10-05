import { Star, StarHalf } from 'lucide-react';
import { cx } from '../lib/store';

/** 5-star rating with half stars; `size` in px. */
export default function Stars({ rating = 0, size = 12, className = 'text-amber-400', empty = 'text-gray-200' }) {
    return (
        <span className={cx('flex gap-0.5', className)} role="img" aria-label={`${rating} / 5`}>
            {[1, 2, 3, 4, 5].map((i) => {
                if (i <= Math.floor(rating)) return <Star key={i} size={size} fill="currentColor" strokeWidth={0} />;
                if (i - 0.5 <= rating) {
                    return (
                        <span key={i} className="relative inline-flex">
                            <Star size={size} className={empty} fill="currentColor" strokeWidth={0} />
                            <StarHalf size={size} className="absolute inset-0 rtl:-scale-x-100" fill="currentColor" strokeWidth={0} />
                        </span>
                    );
                }
                return <Star key={i} size={size} className={empty} fill="currentColor" strokeWidth={0} />;
            })}
        </span>
    );
}
