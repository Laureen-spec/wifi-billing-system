import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { LockKeyhole, Paperclip, Send } from 'lucide-react';
import MediaPicker from '@/components/MediaPicker';
import { ReplyFormProps } from './types';

export default function ReplyForm({ ticketId, onReplyAdded, disabled }: ReplyFormProps) {
    const { t } = useTranslation();
    const [message, setMessage] = useState('');
    const [attachments, setAttachments] = useState<string[]>([]);
    const [isInternal, setIsInternal] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [editorKey, setEditorKey] = useState(0);
    const { auth } = usePage<any>().props;

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!message.trim() || isSubmitting) return;

        setIsSubmitting(true);

        try {
            const payload = {
                message,
                is_internal: isInternal,
                attachments: attachments || null,
            };

            const response = await fetch(route('helpdesk-replies.store', ticketId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (data.success) {
                setMessage('');
                setAttachments([]);
                setIsInternal(false);
                setEditorKey((prev) => prev + 1);
                onReplyAdded(data.reply);
            } else {
                console.error('Failed to send reply');
            }
        } catch (error) {
            console.error('Error sending reply:', error);
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <form onSubmit={handleSubmit} className="border-t border-slate-200 bg-slate-50/90 p-4">
            <div className="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                <RichTextEditor
                    key={editorKey}
                    content={message}
                    onChange={(content) => setMessage(content)}
                    placeholder={t('Write a clear customer reply...')}
                    disabled={disabled || isSubmitting}
                    className="min-h-[90px] border-0 shadow-none"
                    onKeyDown={(e: React.KeyboardEvent) => {
                        e.stopPropagation();
                    }}
                />

                <div className="mt-3 flex flex-col gap-3 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-wrap items-center gap-3">
                        <div className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                            <Paperclip className="h-4 w-4 text-slate-500" />
                            <MediaPicker
                                label=""
                                value={attachments}
                                onChange={(value) => setAttachments(Array.isArray(value) ? value : [value].filter(Boolean))}
                                multiple={true}
                                placeholder={t('Attach')}
                                showPreview={false}
                                disabled={disabled || isSubmitting}
                            />
                        </div>

                        {auth.user?.type === 'superadmin' && (
                            <div className="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2">
                                <Checkbox
                                    id="is_internal"
                                    checked={isInternal}
                                    onCheckedChange={(checked) => setIsInternal(!!checked)}
                                    disabled={disabled || isSubmitting}
                                    className="h-4 w-4"
                                />
                                <label htmlFor="is_internal" className="flex cursor-pointer items-center gap-1.5 text-xs font-semibold text-amber-800">
                                    <LockKeyhole className="h-3.5 w-3.5" />
                                    {t('Internal note')}
                                </label>
                            </div>
                        )}
                    </div>

                    <Button
                        type="submit"
                        disabled={!message.trim() || disabled || isSubmitting}
                        className="rounded-xl bg-emerald-600 px-5 text-white hover:bg-emerald-700"
                    >
                        <Send className="mr-2 h-4 w-4" />
                        {isSubmitting ? t('Sending...') : t('Send reply')}
                    </Button>
                </div>
            </div>
        </form>
    );
}
