import { useState } from 'react';
import { ArrowLeft, ArrowRight, Pencil, Plus, Trash2, X } from 'lucide-react';
import { Button, Field, Input, Select, Textarea } from './ui';
import { Toggle } from './crud';
import MediaPicker from './MediaPicker';
import { ColorField } from './schemaForm';
import { storageUrl } from '../lib/http';

const blank = {
    image: '', image_path: '', badge: '', title: '', subtitle: '', button_text: 'Shop Now', button_link: '/shop',
    secondary_button_text: '', secondary_button_link: '', text_color: '#1f2937', text_align: 'left', overlay_opacity: '0',
    show_features: false, feature_1: '', feature_2: '', feature_3: '',
};

const fresh = () => ({
    ...blank,
    badge: `New Collection ${new Date().getFullYear()}`, title: 'Discover Premium Products',
    subtitle: 'Shop the latest trends with exclusive deals',
    feature_1: 'Free Shipping', feature_2: 'Secure Payment', feature_3: 'Easy Returns',
});

function SlideModal({ slide, isNew, onSave, onClose }) {
    const [s, setS] = useState(slide);
    const up = (k) => (e) => setS((c) => ({ ...c, [k]: e.target.value }));

    return (
        <div className="fixed inset-0 z-[70] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label={isNew ? 'Add slide' : 'Edit slide'}>
            <div className="absolute inset-0 bg-black/60" onClick={onClose} />
            <div className="relative max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-card bg-surface p-4 shadow-xl md:p-6">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="text-lg font-semibold">{isNew ? 'Add New Slide' : 'Edit Slide'}</h3>
                    <button type="button" onClick={onClose} aria-label="Close" className="text-muted hover:text-ink"><X size={18} /></button>
                </div>

                <div className="space-y-4">
                    <div>
                        <p className="mb-1 text-sm font-medium">Slide Image *</p>
                        {s.image && <img src={s.image} alt="" className="mb-2 h-40 w-full rounded-lg object-cover" />}
                        {/* the picker deals in library paths; the preview URL is derived from the path */}
                        <MediaPicker
                            value={s.image_path} collection="pages" label={s.image ? 'Change Image' : 'Select from Media Library'}
                            onChange={(path) => setS((c) => ({ ...c, image_path: path, image: path ? storageUrl(path) : '' }))}
                        />
                        <p className="mt-1 text-xs text-muted">Recommended size: 1920×800px</p>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="Badge Text"><Input value={s.badge} onChange={up('badge')} placeholder="e.g., New Collection 2025" /></Field>
                        <Field label="Title"><Input value={s.title} onChange={up('title')} placeholder="e.g., Discover Premium Products" /></Field>
                        <Field label="Subtitle" className="md:col-span-2"><Textarea rows={2} value={s.subtitle} onChange={up('subtitle')} /></Field>
                        <Field label="Primary Button Text"><Input value={s.button_text} onChange={up('button_text')} placeholder="e.g., Shop Now" /></Field>
                        <Field label="Primary Button Link"><Input value={s.button_link} onChange={up('button_link')} placeholder="e.g., /shop" /></Field>
                        <Field label="Secondary Button Text (optional)"><Input value={s.secondary_button_text} onChange={up('secondary_button_text')} placeholder="e.g., Learn More" /></Field>
                        <Field label="Secondary Button Link"><Input value={s.secondary_button_link} onChange={up('secondary_button_link')} placeholder="e.g., /about" /></Field>
                    </div>

                    <h4 className="border-t border-line pt-4 text-sm font-semibold">Style Options</h4>
                    <div className="grid gap-4 md:grid-cols-3">
                        <Field label="Text Color"><ColorField value={s.text_color} onChange={(v) => setS((c) => ({ ...c, text_color: v }))} label="Text color" /></Field>
                        <Field label="Text Alignment">
                            <Select value={s.text_align} onChange={up('text_align')}>
                                <option value="left">Left</option><option value="center">Center</option><option value="right">Right</option>
                            </Select>
                        </Field>
                        <Field label="Overlay Opacity">
                            <Select value={String(s.overlay_opacity)} onChange={up('overlay_opacity')}>
                                <option value="0">None</option>
                                {[1, 2, 3, 4, 5].map((n) => <option key={n} value={`0.${n}`}>{n * 10}%</option>)}
                            </Select>
                        </Field>
                    </div>

                    <h4 className="border-t border-line pt-4 text-sm font-semibold">Feature Highlights <span className="font-normal text-muted">(optional)</span></h4>
                    <Toggle checked={s.show_features} onChange={(v) => setS((c) => ({ ...c, show_features: v }))} label="Show feature highlights on this slide" />
                    {s.show_features && (
                        <div className="grid gap-4 md:grid-cols-3">
                            {[1, 2, 3].map((n) => (
                                <Field key={n} label={`Feature ${n}`}><Input value={s[`feature_${n}`]} onChange={up(`feature_${n}`)} placeholder={['e.g., Free Shipping', 'e.g., Secure Payment', 'e.g., Easy Returns'][n - 1]} /></Field>
                            ))}
                        </div>
                    )}
                </div>

                <div className="mt-6 flex justify-end gap-2">
                    <Button tone="neutral" onClick={onClose}>Cancel</Button>
                    <Button onClick={() => onSave(s)} disabled={!s.image}>{isNew ? 'Add Slide' : 'Update Slide'}</Button>
                </div>
            </div>
        </div>
    );
}

/** Add / edit / reorder / remove the hero slider slides. `slides` is an array of slide objects. */
export default function HeroSlides({ slides, onChange }) {
    const [editing, setEditing] = useState(null); // { index|null, slide }

    const save = (slide) => {
        const clean = { ...slide, show_features: !!slide.show_features };
        onChange(editing.index === null ? [...slides, clean] : slides.map((s, i) => (i === editing.index ? clean : s)));
        setEditing(null);
    };
    const remove = (i) => { if (window.confirm('Are you sure you want to remove this slide?')) onChange(slides.filter((_, n) => n !== i)); };
    const move = (i, dir) => {
        const j = i + dir;
        if (j < 0 || j >= slides.length) return;
        const next = [...slides];
        [next[i], next[j]] = [next[j], next[i]];
        onChange(next);
    };

    return (
        <div>
            <p className="mb-3 rounded-lg border border-info/40 bg-info/10 p-3 text-sm text-muted">Each slide has its own content. Use the edit button on a slide to customize its title, subtitle, buttons and styling.</p>
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                {slides.map((s, i) => (
                    <div key={i} className="overflow-hidden rounded-lg border border-line">
                        <div className="relative aspect-video bg-surface-2">
                            {s.image && <img src={s.image} alt={s.title || `Slide ${i + 1}`} className="h-full w-full object-cover" />}
                            <span className="absolute left-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-black/60 text-xs text-white">{i + 1}</span>
                        </div>
                        <div className="flex items-center justify-between gap-1 p-2">
                            <div className="flex gap-1">
                                <button type="button" onClick={() => move(i, -1)} disabled={i === 0} aria-label={`Move slide ${i + 1} earlier`} className="text-muted hover:text-ink disabled:opacity-30"><ArrowLeft size={16} /></button>
                                <button type="button" onClick={() => move(i, 1)} disabled={i === slides.length - 1} aria-label={`Move slide ${i + 1} later`} className="text-muted hover:text-ink disabled:opacity-30"><ArrowRight size={16} /></button>
                            </div>
                            <div className="flex gap-2">
                                <button type="button" onClick={() => setEditing({ index: i, slide: { ...blank, ...s } })} aria-label={`Edit slide ${i + 1}`} className="text-primary"><Pencil size={16} /></button>
                                <button type="button" onClick={() => remove(i)} aria-label={`Remove slide ${i + 1}`} className="text-danger"><Trash2 size={16} /></button>
                            </div>
                        </div>
                    </div>
                ))}
                <button type="button" onClick={() => setEditing({ index: null, slide: fresh() })} className="flex aspect-video flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-line text-muted hover:border-primary hover:text-primary">
                    <Plus size={22} /><span className="text-sm">Add Slide</span>
                </button>
            </div>
            {editing && <SlideModal slide={editing.slide} isNew={editing.index === null} onSave={save} onClose={() => setEditing(null)} />}
        </div>
    );
}
