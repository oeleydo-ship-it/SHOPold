import { Badge, titleCase } from './ui';

const STATUS_TONE = { open: 'info', in_progress: 'primary', waiting: 'warning', resolved: 'success', closed: 'muted', active: 'success', ended: 'muted', converted_to_ticket: 'primary' };
const PRIORITY_TONE = { low: 'muted', medium: 'info', high: 'warning', urgent: 'danger' };

export const TicketStatus = ({ status }) => <Badge tone={STATUS_TONE[status] ?? 'muted'}>{titleCase(status)}</Badge>;
export const TicketPriority = ({ priority }) => <Badge tone={PRIORITY_TONE[priority] ?? 'muted'}>{titleCase(priority)}</Badge>;
