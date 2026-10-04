import { useEffect, useRef } from 'react';
import DOMPurify from 'dompurify';
import {
    AlignCenter, AlignLeft, AlignRight, Bold, Eraser, Italic, Link2, List, ListOrdered, Minus, Redo2, Strikethrough,
    Underline, Undo2, Unlink,
} from 'lucide-react';

function ToolButton({ title, onClick, children }) {
    return (
        <button
            type="button"
            title={title}
            aria-label={title}
            // mousedown keeps the editor selection; click would blur it first
            onMouseDown={(e) => { e.preventDefault(); onClick(); }}
            className="flex h-8 w-8 items-center justify-center rounded text-muted hover:bg-surface-2 hover:text-ink"
        >
            {children}
        </button>
    );
}

/**
 * Minimal contenteditable HTML editor (same execCommand approach as the old Blade editor).
 * `value` is only written into the DOM when it differs from what the user last typed, so
 * external updates (e.g. AI generation) work without moving the caret while typing.
 */
export default function RichEditor({ value, onChange, placeholder, height = 320, id }) {
    const ref = useRef(null);
    const lastEmitted = useRef(null);

    // Stored HTML can come from another admin, so it is sanitised before it is put into the editor
    // (otherwise something like <img onerror=...> would run for whoever opens the page).
    // Text the user is typing right now is skipped, so the caret never jumps.
    useEffect(() => {
        if (!ref.current || value === lastEmitted.current) return;
        ref.current.innerHTML = DOMPurify.sanitize(value ?? '');
    }, [value]);

    const emit = (html) => {
        lastEmitted.current = html;
        onChange(html);
    };

    const exec = (cmd, arg = null) => {
        ref.current?.focus();
        document.execCommand(cmd, false, arg);
        emit(ref.current.innerHTML);
    };

    const link = () => {
        const url = window.prompt('Link URL (https://…)');
        if (url && /^(https?:|mailto:|\/)/i.test(url)) exec('createLink', url);
    };

    return (
        <div className="overflow-hidden rounded-lg border border-line bg-surface focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/30">
            <div className="flex flex-wrap items-center gap-0.5 border-b border-line bg-surface-2 p-1.5">
                <select
                    aria-label="Text style"
                    defaultValue=""
                    onChange={(e) => { if (e.target.value) exec('formatBlock', e.target.value); e.target.value = ''; }}
                    className="h-8 rounded border border-line bg-surface px-1 text-xs text-ink"
                >
                    <option value="">Style</option>
                    <option value="p">Paragraph</option>
                    <option value="h2">Heading 2</option>
                    <option value="h3">Heading 3</option>
                    <option value="h4">Heading 4</option>
                    <option value="blockquote">Quote</option>
                </select>
                <ToolButton title="Bold" onClick={() => exec('bold')}><Bold size={15} /></ToolButton>
                <ToolButton title="Italic" onClick={() => exec('italic')}><Italic size={15} /></ToolButton>
                <ToolButton title="Underline" onClick={() => exec('underline')}><Underline size={15} /></ToolButton>
                <ToolButton title="Strikethrough" onClick={() => exec('strikeThrough')}><Strikethrough size={15} /></ToolButton>
                <span className="mx-1 h-5 w-px bg-line" />
                <ToolButton title="Bullet list" onClick={() => exec('insertUnorderedList')}><List size={15} /></ToolButton>
                <ToolButton title="Numbered list" onClick={() => exec('insertOrderedList')}><ListOrdered size={15} /></ToolButton>
                <span className="mx-1 h-5 w-px bg-line" />
                <ToolButton title="Align left" onClick={() => exec('justifyLeft')}><AlignLeft size={15} /></ToolButton>
                <ToolButton title="Align center" onClick={() => exec('justifyCenter')}><AlignCenter size={15} /></ToolButton>
                <ToolButton title="Align right" onClick={() => exec('justifyRight')}><AlignRight size={15} /></ToolButton>
                <span className="mx-1 h-5 w-px bg-line" />
                <ToolButton title="Insert link" onClick={link}><Link2 size={15} /></ToolButton>
                <ToolButton title="Remove link" onClick={() => exec('unlink')}><Unlink size={15} /></ToolButton>
                <ToolButton title="Horizontal line" onClick={() => exec('insertHorizontalRule')}><Minus size={15} /></ToolButton>
                <span className="mx-1 h-5 w-px bg-line" />
                <ToolButton title="Undo" onClick={() => exec('undo')}><Undo2 size={15} /></ToolButton>
                <ToolButton title="Redo" onClick={() => exec('redo')}><Redo2 size={15} /></ToolButton>
                <ToolButton title="Clear formatting" onClick={() => exec('removeFormat')}><Eraser size={15} /></ToolButton>
            </div>
            <div
                id={id}
                ref={ref}
                role="textbox"
                aria-multiline="true"
                contentEditable
                suppressContentEditableWarning
                data-placeholder={placeholder}
                onInput={(e) => emit(e.currentTarget.innerHTML)}
                style={{ minHeight: height }}
                className="rich-content overflow-y-auto px-4 py-3 text-sm text-ink outline-none empty:before:text-muted empty:before:content-[attr(data-placeholder)]"
            />
        </div>
    );
}
