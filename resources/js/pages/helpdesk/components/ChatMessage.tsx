import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Download, Paperclip, Trash2 } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { ChatMessageProps } from './types';
import { formatDateTime, getImagePath, downloadFile } from '@/utils/helpers';
import { isImageFile } from '@/utils/fileHelpers';

export default function ChatMessage({ reply, isOwnMessage, onDelete, canDelete }: ChatMessageProps) {
    const { t } = useTranslation();
    const [showActions, setShowActions] = useState(false);

    const attachments = (() => {
        if (!reply.attachments) return [] as string[];
        if (typeof reply.attachments === 'string') {
            try {
                return JSON.parse(reply.attachments);
            } catch {
                return [reply.attachments];
            }
        }
        return Array.isArray(reply.attachments) ? reply.attachments : [];
    })();

    const bubbleClass = reply.is_internal
        ? 'border border-amber-200 bg-amber-50 text-amber-950 shadow-sm'
        : isOwnMessage
            ? 'bg-slate-950 text-white shadow-sm'
            : 'border border-slate-200 bg-slate-50 text-slate-900 shadow-sm';

    const nameClass = reply.is_internal
        ? 'text-amber-800'
        : isOwnMessage
            ? 'text-white'
            : 'text-slate-700';

    return (
        <div className={`flex ${isOwnMessage ? 'justify-end' : 'justify-start'}`}>
            <div className={`max-w-[86%] sm:max-w-[72%] ${isOwnMessage ? 'items-end' : 'items-start'} flex flex-col gap-1.5`}>
                <div
                    className={`relative rounded-2xl p-4 ${isOwnMessage ? 'rounded-br-md' : 'rounded-bl-md'} ${bubbleClass}`}
                    onMouseEnter={() => setShowActions(true)}
                    onMouseLeave={() => setShowActions(false)}
                >
                    <div className="mb-2 flex items-center justify-between gap-3">
                        <div className="flex min-w-0 items-center gap-2">
                            <span className={`truncate text-sm font-bold ${nameClass}`}>{reply.creator?.name || t('Unknown')}</span>
                            {reply.is_internal && (
                                <span className="rounded-full bg-amber-200 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-900">
                                    {t('Internal Note')}
                                </span>
                            )}
                        </div>

                        {showActions && canDelete && onDelete && (
                            <TooltipProvider>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => onDelete(reply.id)}
                                            className={`h-7 w-7 rounded-full p-0 ${
                                                isOwnMessage
                                                    ? 'text-white/70 hover:bg-white/10 hover:text-white'
                                                    : 'text-slate-400 hover:bg-red-50 hover:text-red-600'
                                            }`}
                                        >
                                            <Trash2 className="h-3.5 w-3.5" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent><p>{t('Delete')}</p></TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        )}
                    </div>

                    <div className="text-sm leading-6" dangerouslySetInnerHTML={{ __html: reply.message }} />

                    {attachments.length > 0 && (
                        <div className="mt-3 space-y-2">
                            {attachments.map((attachment: string, index: number) => {
                                const isImage = isImageFile(attachment);
                                const fileUrl = getImagePath(attachment);
                                return (
                                    <div
                                        key={`${attachment}-${index}`}
                                        className={`flex items-center gap-3 rounded-xl p-2 ${
                                            isOwnMessage ? 'bg-white/10' : 'bg-white'
                                        }`}
                                    >
                                        {isImage ? (
                                            <img src={fileUrl} alt={t('Attachment preview')} className="h-14 w-14 rounded-lg object-cover" />
                                        ) : (
                                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                                <Paperclip className="h-4 w-4" />
                                            </div>
                                        )}
                                        <span className="min-w-0 flex-1 truncate text-xs font-medium">{attachment}</span>
                                        <Button variant="ghost" size="sm" onClick={() => downloadFile(fileUrl)} className="h-8 w-8 rounded-lg p-0">
                                            <Download className="h-4 w-4" />
                                        </Button>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>

                <div className={`text-xs text-slate-500 ${isOwnMessage ? 'text-right' : 'text-left'}`}>
                    {formatDateTime(reply.created_at)}
                </div>
            </div>
        </div>
    );
}
