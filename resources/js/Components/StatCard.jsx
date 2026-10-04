import { Card } from './ui';

/** Small KPI tile: label, big value and a coloured icon (tone = a theme colour name: primary, success, warning, danger, info). */
export default function StatCard({ label, value, icon: Icon, tone = 'primary', sub }) {
    const color = `var(--${tone})`;
    return (
        <Card className="flex items-center gap-4 p-4">
            {Icon && (
                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg" style={{ color, background: `color-mix(in srgb, ${color} 15%, transparent)` }}>
                    <Icon size={20} />
                </span>
            )}
            <div className="min-w-0">
                <p className="text-xs text-muted md:text-sm">{label}</p>
                <p className="truncate text-xl font-bold md:text-2xl">{value}</p>
                {sub}
            </div>
        </Card>
    );
}
