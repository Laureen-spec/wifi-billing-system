import { useState, useEffect, useRef, useMemo, ReactNode } from 'react';
import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useFlashMessages } from '@/hooks/useFlashMessages';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import {
    Activity,
    Bot,
    ChevronDown,
    ChevronUp,
    CreditCard,
    FileText,
    Loader2,
    MessageSquare,
    Plus,
    Receipt,
    Search,
    Send,
    ShieldCheck,
    Sparkles,
    Trash2,
    User,
    Wallet,
    Wifi,
    X,
} from 'lucide-react';

interface ChatMessage {
    id?: number;
    role: 'user' | 'assistant';
    content: string;
    created_at?: string;
}

interface ChatSession {
    id: number;
    title: string;
    updated_at: string;
    last_message?: { content: string; role: string };
}

interface Props {
    sessions: ChatSession[];
}

const toggleMessageExpansion = (setExpandedMessages: React.Dispatch<React.SetStateAction<Set<number>>>, index: number) => {
    setExpandedMessages(prev => {
        const newSet = new Set(prev);
        if (newSet.has(index)) {
            newSet.delete(index);
        } else {
            newSet.add(index);
        }
        return newSet;
    });
};

const isMessageLong = (content: string) => content.length > 500;
const getTruncatedContent = (content: string) => content.substring(0, 500) + '...';

const groupSessionsByDate = (sessions: ChatSession[]) => {
    const today     = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    const weekAgo   = new Date(today);
    weekAgo.setDate(weekAgo.getDate() - 7);

    const groups: Record<string, ChatSession[]> = {
        Today: [],
        Yesterday: [],
        'This Week': [],
        Older: [],
    };

    sessions.forEach(s => {
        const d = new Date(s.updated_at);
        if (d.toDateString() === today.toDateString()) {
            groups['Today'].push(s);
        } else if (d.toDateString() === yesterday.toDateString()) {
            groups['Yesterday'].push(s);
        } else if (d >= weekAgo) {
            groups['This Week'].push(s);
        } else {
            groups['Older'].push(s);
        }
    });

    return groups;
};

const MessageInput = ({ onSend, loading, inputRef }: { onSend: (msg: string) => void; loading: boolean; inputRef: React.RefObject<HTMLTextAreaElement> }) => {
    const [value, setValue] = useState('');

    const handleSend = () => {
        if (value.trim() && !loading) {
            onSend(value.trim());
            setValue('');
            if (inputRef.current) {
                inputRef.current.style.height = 'auto';
            }
        }
    };

    const handleChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
        setValue(e.target.value);
        e.target.style.height = 'auto';
        e.target.style.height = Math.min(e.target.scrollHeight, 180) + 'px';
    };

    return (
        <div className="border-t bg-card p-4">
            <div className="flex gap-3 rounded-2xl border bg-background p-2 shadow-sm transition-all focus-within:border-primary/40 focus-within:ring-2 focus-within:ring-primary/10">
                <textarea
                    ref={inputRef}
                    rows={1}
                    value={value}
                    onChange={handleChange}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            handleSend();
                        }
                    }}
                    placeholder="Ask about billing, subscribers, routers, payments, SMS, or support..."
                    disabled={loading}
                    className="max-h-[180px] min-h-[42px] flex-1 resize-none overflow-y-auto bg-transparent px-3 py-2 text-sm text-foreground outline-none placeholder:text-muted-foreground disabled:opacity-50"
                />
                <Button
                    onClick={handleSend}
                    disabled={loading || !value.trim()}
                    className="h-10 shrink-0 rounded-xl px-4"
                >
                    {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
                    Send
                </Button>
            </div>
            <p className="mt-2 text-xs text-muted-foreground">Press Enter to send. Use Shift + Enter for a new line.</p>
        </div>
    );
};

export default function AIAgentChatPage({ sessions: initialSessions }: Props) {
    const { t } = useTranslation();

    useFlashMessages();

    const [sessions, setSessions]           = useState<ChatSession[]>(initialSessions);
    const [activeSession, setActiveSession] = useState<ChatSession | null>(null);
    const [messages, setMessages]           = useState<ChatMessage[]>([]);
    const [searchQuery, setSearchQuery]     = useState('');
    const [loading, setLoading]             = useState(false);
    const [loadingMessages, setLoadingMessages] = useState(false);
    const [deleteTarget, setDeleteTarget]   = useState<ChatSession | null>(null);
    const [expandedMessages, setExpandedMessages] = useState<Set<number>>(new Set());
    const bottomRef                         = useRef<HTMLDivElement>(null);
    const inputRef                          = useRef<HTMLTextAreaElement>(null);

    const filteredSessions = useMemo(() =>
        sessions.filter(session =>
            session.title.toLowerCase().includes(searchQuery.toLowerCase())
        ),
        [sessions, searchQuery]
    );

    const grouped = useMemo(() => groupSessionsByDate(filteredSessions), [filteredSessions]);

    const suggestedPrompts = useMemo(() => [
        { text: t('Check expired subscriptions'), icon: FileText },
        { text: t('Explain today’s payments'), icon: Receipt },
        { text: t('Router troubleshooting'), icon: Wifi },
        { text: t('SMS balance help'), icon: Wallet },
    ], [t]);

    const assistantCards = useMemo(() => [
        { title: t('Billing assistant'), text: t('Plans, invoices, trials, renewals, and expired subscriptions.'), icon: CreditCard },
        { title: t('Router support'), text: t('Router status, hotspot checks, CPE notes, and troubleshooting steps.'), icon: Wifi },
        { title: t('Payment checks'), text: t('M-Pesa follow-up, orders, billing records, and payment summaries.'), icon: Wallet },
        { title: t('SMS help'), text: t('SMS balance, top-ups, templates, alerts, and delivery logs.'), icon: MessageSquare },
    ], [t]);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, loading]);

    const loadSession = async (session: ChatSession) => {
        setActiveSession(session);
        setLoadingMessages(true);
        setMessages([]);

        try {
            const res = await fetch(route('ai-agent.sessions.messages', session.id), {
                headers: { 'Accept': 'application/json' },
            });

            const data = await res.json();

            if (!res.ok) {
                setMessages([]);
                return;
            }

            setMessages(data);
        } catch {
            setMessages([]);
        } finally {
            setLoadingMessages(false);
            setTimeout(() => inputRef.current?.focus(), 100);
        }
    };

    const createNewChat = () => {
        setActiveSession(null);
        setMessages([]);
        setTimeout(() => inputRef.current?.focus(), 100);
    };

    const deleteSession = (session: ChatSession) => {
        router.delete(route('ai-agent.sessions.destroy', session.id), {
            preserveState: true,
            onSuccess: () => {
                const remaining = sessions.filter(s => s.id !== session.id);
                setSessions(remaining);
                setDeleteTarget(null);

                if (activeSession?.id === session.id) {
                    setActiveSession(null);
                    setMessages([]);
                }
            },
            onFinish: () => {
                setDeleteTarget(null);
            }
        });
    };

    const sendMessage = async (message: string) => {
        if (!message || loading) return;

        let currentSession = activeSession;
        let isNewSession = false;

        if (!currentSession) {
            setLoading(true);

            try {
                const res = await fetch(route('ai-agent.sessions.store'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                });

                if (!res.ok) {
                    setLoading(false);
                    return;
                }

                const newSession = await res.json();
                setSessions(prev => [newSession, ...prev]);
                setActiveSession(newSession);
                currentSession = newSession;
                isNewSession = true;

                await proceedWithMessage(newSession, message, isNewSession);
            } catch {
                setLoading(false);
                return;
            }
            return;
        }

        proceedWithMessage(currentSession, message, isNewSession);
    };

    const proceedWithMessage = async (currentSession: ChatSession, message: string, isNewSession: boolean) => {
        const userMsg: ChatMessage = { role: 'user', content: message };
        setMessages(prev => [...prev, userMsg]);
        setLoading(true);

        const history = [...messages, userMsg]
            .slice(-6)
            .map(m => ({ role: m.role, content: m.content }));

        try {
            const res = await fetch(route('ai-agent.chat'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    message,
                    session_id: currentSession.id,
                    history,
                }),
            });

            const data = await res.json();

            if (!res.ok) {
                setMessages(prev => prev.slice(0, -1));
                return;
            }

            const reply = data.reply ?? t('Something went wrong. Please try again.');

            setMessages(prev => [...prev, { role: 'assistant', content: reply }]);

            if (currentSession.title === 'New Chat') {
                const newTitle = message.substring(0, 60);
                setActiveSession(prev => prev ? { ...prev, title: newTitle } : prev);
                setSessions(prev => prev.map(s =>
                    s.id === currentSession.id
                        ? { ...s, title: newTitle, updated_at: new Date().toISOString() }
                        : s
                ));
            } else {
                setSessions(prev => {
                    const updated = prev.map(s =>
                        s.id === currentSession.id ? { ...s, updated_at: new Date().toISOString() } : s
                    );
                    return [...updated].sort((a, b) =>
                        new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime()
                    );
                });
            }

            if (isNewSession) {
                const msgRes = await fetch(route('ai-agent.sessions.messages', currentSession.id), {
                    headers: { 'Accept': 'application/json' },
                });
                const savedMessages = await msgRes.json();
                setMessages(savedMessages);
            }
        } catch {
            setMessages(prev => [...prev, { role: 'assistant', content: t('Network error. Please try again.') }]);
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('AI Agent') }]}
            pageTitle={t('AI Agent')}
        >
            <Head title={t('AI Agent')} />

            <div className="space-y-5">
                <div className="rounded-2xl border bg-card p-5 shadow-sm">
                    <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <div className="inline-flex items-center gap-2 rounded-full border bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                                <Sparkles className="h-3.5 w-3.5" />
                                StudyRoom Connect assistant
                            </div>
                            <h1 className="mt-3 text-3xl font-semibold tracking-tight text-foreground">AI Agent</h1>
                            <p className="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground">
                                Ask billing, subscribers, routers, payments, SMS, and support questions from one workspace.
                            </p>
                        </div>
                        <div className="grid gap-3 sm:grid-cols-3">
                            <HeaderMetric icon={<ShieldCheck className="h-4 w-4" />} label="Workspace" value="Billing system" />
                            <HeaderMetric icon={<Activity className="h-4 w-4" />} label="Conversations" value={String(sessions.length)} />
                            <HeaderMetric icon={<MessageSquare className="h-4 w-4" />} label="Mode" value="Support ready" />
                        </div>
                    </div>
                </div>

                <div className="grid gap-5 xl:grid-cols-[290px_minmax(0,1fr)_320px]">
                    <aside className="rounded-2xl border bg-card shadow-sm xl:min-h-[680px]">
                        <div className="space-y-3 border-b p-4">
                            <Button
                                onClick={createNewChat}
                                className="w-full gap-2 rounded-xl"
                                size="sm"
                            >
                                <Plus className="h-4 w-4" />
                                {t('New Chat')}
                            </Button>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <input
                                    type="text"
                                    placeholder={t('Search chats...')}
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="h-10 w-full rounded-xl border bg-background pl-9 pr-9 text-sm outline-none transition focus:border-primary/40 focus:ring-2 focus:ring-primary/10"
                                />
                                {searchQuery && (
                                    <button
                                        onClick={() => setSearchQuery('')}
                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                )}
                            </div>
                        </div>

                        <div className="max-h-[590px] space-y-4 overflow-y-auto p-3">
                            {filteredSessions.length === 0 && searchQuery && (
                                <EmptyState icon={<Search className="h-6 w-6" />} title="No chats found" text="Try a different search term." />
                            )}
                            {sessions.length === 0 && !searchQuery && (
                                <EmptyState icon={<MessageSquare className="h-6 w-6" />} title="No conversations yet" text="Start a new AI support chat." />
                            )}

                            {Object.entries(grouped).map(([group, items]) =>
                                items.length === 0 ? null : (
                                    <div key={group}>
                                        <p className="px-2 pb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">{t(group)}</p>
                                        <div className="space-y-1">
                                            {items.map(session => (
                                                <div
                                                    key={session.id}
                                                    onClick={() => loadSession(session)}
                                                    className={`group flex cursor-pointer items-center justify-between gap-2 rounded-xl px-3 py-2.5 transition-all ${
                                                        activeSession?.id === session.id
                                                            ? 'border border-primary/20 bg-primary/10 text-primary'
                                                            : 'border border-transparent text-foreground hover:border-border hover:bg-muted/50'
                                                    }`}
                                                >
                                                    <div className="min-w-0">
                                                        <p className="truncate text-sm font-medium">{session.title}</p>
                                                        {session.last_message?.content && (
                                                            <p className="truncate text-xs text-muted-foreground">{session.last_message.content}</p>
                                                        )}
                                                    </div>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-7 w-7 shrink-0 opacity-0 transition-opacity group-hover:opacity-100 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                        onClick={e => { e.stopPropagation(); setDeleteTarget(session); }}
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )
                            )}
                        </div>
                    </aside>

                    <section className="flex min-h-[680px] flex-col overflow-hidden rounded-2xl border bg-card shadow-sm">
                        <div className="flex items-center justify-between border-b bg-muted/20 px-5 py-4">
                            <div>
                                <p className="text-sm font-semibold text-foreground">{activeSession?.title || 'Billing assistant workspace'}</p>
                                <p className="text-xs text-muted-foreground">Use it for operational questions across billing, routers, payments, SMS, and support.</p>
                            </div>
                            <div className="hidden items-center gap-2 rounded-full border bg-background px-3 py-1 text-xs text-muted-foreground sm:flex">
                                <Bot className="h-3.5 w-3.5 text-primary" />
                                AI Agent online
                            </div>
                        </div>

                        <div className="flex-1 overflow-y-auto bg-background/60">
                            {!activeSession && messages.length === 0 && (
                                <div className="flex h-full flex-col justify-center px-5 py-10">
                                    <div className="mx-auto max-w-2xl text-center">
                                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border bg-card text-primary shadow-sm">
                                            <Bot className="h-8 w-8" />
                                        </div>
                                        <h2 className="mt-5 text-2xl font-semibold text-foreground">Welcome to AI Agent</h2>
                                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                            Start with a billing question, router issue, SMS balance check, or payment summary request.
                                        </p>
                                        <div className="mt-6 grid gap-3 sm:grid-cols-2">
                                            {suggestedPrompts.map((prompt, i) => {
                                                const Icon = prompt.icon;
                                                return (
                                                    <button
                                                        key={i}
                                                        onClick={() => sendMessage(prompt.text)}
                                                        className="rounded-2xl border bg-card p-4 text-left transition hover:border-primary/30 hover:bg-primary/5"
                                                    >
                                                        <Icon className="h-5 w-5 text-primary" />
                                                        <p className="mt-3 text-sm font-medium text-foreground">{prompt.text}</p>
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {activeSession && loadingMessages && (
                                <div className="flex justify-center py-16">
                                    <Loader2 className="h-6 w-6 animate-spin text-primary" />
                                </div>
                            )}

                            {activeSession && !loadingMessages && messages.length === 0 && (
                                <div className="flex h-full flex-col items-center justify-center px-4 text-center">
                                    <div className="rounded-2xl border bg-card p-5 text-primary shadow-sm">
                                        <MessageSquare className="h-8 w-8" />
                                    </div>
                                    <p className="mt-4 text-sm text-muted-foreground">{t('Start a conversation with AI Agent')}</p>
                                </div>
                            )}

                            {!loadingMessages && messages.length > 0 && (
                                <div className="space-y-5 px-5 py-6">
                                    {messages.map((msg, i) => (
                                        <MessageBubble
                                            key={i}
                                            message={msg}
                                            index={i}
                                            expanded={expandedMessages.has(i)}
                                            onToggle={() => toggleMessageExpansion(setExpandedMessages, i)}
                                        />
                                    ))}

                                    {loading && (
                                        <div className="flex items-start gap-3">
                                            <Avatar className="h-9 w-9 border bg-card">
                                                <AvatarFallback><Bot className="h-4 w-4 text-primary" /></AvatarFallback>
                                            </Avatar>
                                            <div className="rounded-2xl border bg-card px-4 py-3 shadow-sm">
                                                <Loader2 className="h-4 w-4 animate-spin text-primary" />
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            <div ref={bottomRef} />
                        </div>

                        <MessageInput onSend={sendMessage} loading={loading} inputRef={inputRef} />
                    </section>

                    <aside className="space-y-5">
                        <div className="rounded-2xl border bg-card p-4 shadow-sm">
                            <p className="text-sm font-semibold text-foreground">Suggested prompts</p>
                            <div className="mt-3 flex flex-wrap gap-2">
                                {suggestedPrompts.map((prompt) => (
                                    <button
                                        key={prompt.text}
                                        onClick={() => sendMessage(prompt.text)}
                                        className="rounded-full border bg-background px-3 py-1.5 text-xs font-medium text-foreground transition hover:border-primary/40 hover:bg-primary/5 hover:text-primary"
                                    >
                                        {prompt.text}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="rounded-2xl border bg-card p-4 shadow-sm">
                            <p className="text-sm font-semibold text-foreground">Assistant coverage</p>
                            <div className="mt-3 space-y-3">
                                {assistantCards.map((card) => {
                                    const Icon = card.icon;
                                    return (
                                        <div key={card.title} className="rounded-2xl border bg-muted/20 p-3">
                                            <div className="flex items-start gap-3">
                                                <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                                    <Icon className="h-4 w-4" />
                                                </div>
                                                <div>
                                                    <p className="text-sm font-medium text-foreground">{card.title}</p>
                                                    <p className="mt-1 text-xs leading-5 text-muted-foreground">{card.text}</p>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        <div className="rounded-2xl border bg-card p-4 shadow-sm">
                            <p className="text-sm font-semibold text-foreground">Workspace note</p>
                            <p className="mt-2 text-xs leading-5 text-muted-foreground">
                                AI Agent keeps the existing chat route and backend logic. The page is styled to match the billing system dashboard.
                            </p>
                        </div>
                    </aside>
                </div>
            </div>

            <ConfirmationDialog
                open={!!deleteTarget}
                onOpenChange={open => { if (!open) setDeleteTarget(null); }}
                onConfirm={() => deleteTarget && deleteSession(deleteTarget)}
                title={t('Delete Conversation')}
                message={t('Are you sure you want to delete this conversation? All messages will be permanently removed.')}
                confirmText={t('Delete')}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}

function HeaderMetric({ icon, label, value }: { icon: ReactNode; label: string; value: string }) {
    return (
        <div className="min-w-[150px] rounded-2xl border bg-background p-3">
            <div className="flex items-center gap-2 text-muted-foreground">
                {icon}
                <span className="text-xs font-medium">{label}</span>
            </div>
            <p className="mt-2 text-sm font-semibold text-foreground">{value}</p>
        </div>
    );
}

function EmptyState({ icon, title, text }: { icon: ReactNode; title: string; text: string }) {
    return (
        <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed bg-muted/20 px-4 py-10 text-center">
            <div className="text-muted-foreground">{icon}</div>
            <p className="mt-3 text-sm font-medium text-foreground">{title}</p>
            <p className="mt-1 text-xs text-muted-foreground">{text}</p>
        </div>
    );
}

function MessageBubble({ message, index, expanded, onToggle }: { message: ChatMessage; index: number; expanded: boolean; onToggle: () => void }) {
    const isAssistant = message.role === 'assistant';
    const long = isMessageLong(message.content);
    const content = long && !expanded ? getTruncatedContent(message.content) : message.content;

    if (isAssistant) {
        return (
            <div className="flex items-start gap-3">
                <Avatar className="h-9 w-9 border bg-card">
                    <AvatarFallback><Bot className="h-4 w-4 text-primary" /></AvatarFallback>
                </Avatar>
                <div className="max-w-[88%] rounded-2xl border bg-card px-4 py-3 text-sm leading-6 text-foreground shadow-sm">
                    <div className="mb-2 flex items-center gap-2 text-xs font-medium text-muted-foreground">
                        <Bot className="h-3.5 w-3.5 text-primary" />
                        AI Agent
                    </div>
                    <div className="whitespace-pre-wrap">{content}</div>
                    {long && <ExpandButton expanded={expanded} onToggle={onToggle} />}
                </div>
            </div>
        );
    }

    return (
        <div className="flex items-start justify-end gap-3">
            <div className="max-w-[82%] rounded-2xl bg-primary px-4 py-3 text-sm leading-6 text-primary-foreground shadow-sm">
                <div className="whitespace-pre-wrap">{content}</div>
                {long && <ExpandButton expanded={expanded} onToggle={onToggle} light />}
            </div>
            <Avatar className="h-9 w-9 border bg-card">
                <AvatarFallback><User className="h-4 w-4 text-muted-foreground" /></AvatarFallback>
            </Avatar>
        </div>
    );
}

function ExpandButton({ expanded, onToggle, light = false }: { expanded: boolean; onToggle: () => void; light?: boolean }) {
    return (
        <button
            onClick={onToggle}
            className={`mt-2 inline-flex items-center gap-1 text-xs font-medium transition ${light ? 'text-primary-foreground/85 hover:text-primary-foreground' : 'text-primary hover:text-primary/80'}`}
        >
            {expanded ? <ChevronUp className="h-3 w-3" /> : <ChevronDown className="h-3 w-3" />}
            {expanded ? 'Show less' : 'Show more'}
        </button>
    );
}
