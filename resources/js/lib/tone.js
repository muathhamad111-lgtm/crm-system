/**
 * Single source of truth for semantic colour tones.
 *
 * Every status, badge, icon chip and progress bar in the platform picks its
 * colours from here, so a tone means the same thing on every screen:
 *
 *   primary     brand / neutral-positive emphasis
 *   accent      secondary brand emphasis (ideas, engagement)
 *   info        informational, in-flight
 *   success     resolved, healthy, within target
 *   warning     needs attention soon
 *   destructive breached, failed, irreversible
 *   muted       inactive, archived, not applicable
 */

export const TONES = ['primary', 'accent', 'info', 'success', 'warning', 'destructive', 'muted'];

/** Tinted surface + matching foreground — icon chips, soft badges, callouts. */
export const TONE_SOFT = {
    primary: 'bg-primary-soft text-primary',
    accent: 'bg-accent-soft text-accent',
    info: 'bg-info/10 text-info',
    success: 'bg-success/10 text-success',
    warning: 'bg-warning/15 text-warning',
    destructive: 'bg-destructive/10 text-destructive',
    muted: 'bg-muted text-muted-foreground',
};

/** Solid fill — primary buttons, filled badges, chart bars. */
export const TONE_SOLID = {
    primary: 'bg-primary text-primary-foreground',
    accent: 'bg-accent text-accent-foreground',
    info: 'bg-info text-info-foreground',
    success: 'bg-success text-success-foreground',
    warning: 'bg-warning text-warning-foreground',
    destructive: 'bg-destructive text-destructive-foreground',
    muted: 'bg-muted text-muted-foreground',
};

/** Foreground only — inline text emphasis. */
export const TONE_TEXT = {
    primary: 'text-primary',
    accent: 'text-accent',
    info: 'text-info',
    success: 'text-success',
    warning: 'text-warning',
    destructive: 'text-destructive',
    muted: 'text-muted-foreground',
};

/** Background only — progress bars, dots, rails. */
export const TONE_FILL = {
    primary: 'bg-primary',
    accent: 'bg-accent',
    info: 'bg-info',
    success: 'bg-success',
    warning: 'bg-warning',
    destructive: 'bg-destructive',
    muted: 'bg-muted-foreground/40',
};

/** Border only — outlined cards that carry a status. */
export const TONE_BORDER = {
    primary: 'border-primary/30',
    accent: 'border-accent/30',
    info: 'border-info/30',
    success: 'border-success/30',
    warning: 'border-warning/35',
    destructive: 'border-destructive/35',
    muted: 'border-border',
};

/** Raw CSS colour, for inline styles and chart series. */
export const TONE_VAR = {
    primary: 'var(--primary)',
    accent: 'var(--accent)',
    info: 'var(--info)',
    success: 'var(--success)',
    warning: 'var(--warning)',
    destructive: 'var(--destructive)',
    muted: 'var(--muted-foreground)',
};

const pick = (map, tone) => map[tone] ?? map.muted;

export const toneSoft = (tone) => pick(TONE_SOFT, tone);
export const toneSolid = (tone) => pick(TONE_SOLID, tone);
export const toneText = (tone) => pick(TONE_TEXT, tone);
export const toneFill = (tone) => pick(TONE_FILL, tone);
export const toneBorder = (tone) => pick(TONE_BORDER, tone);
export const toneVar = (tone) => pick(TONE_VAR, tone);
