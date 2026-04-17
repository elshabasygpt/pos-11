'use client';

import { usePathname } from 'next/navigation';
import { useTheme } from '@/providers/ThemeProvider';
import AuthGuard from '@/components/auth/AuthGuard';
import Sidebar from '@/components/layout/Sidebar';
import { getStoredUser } from '@/lib/auth';
import { useEffect, useState } from 'react';

export default function DashboardLayout({
    children,
    params,
}: {
    children: React.ReactNode;
    params: { locale: string };
}) {
    const locale = params.locale || 'en';
    const isRTL = locale === 'ar';
    const [dict, setDict] = useState<any>(null);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const pathname = usePathname();
    const user = typeof window !== 'undefined' ? getStoredUser() : null;

    // ── POS route → immersive full-screen terminal, no sidebar/header ──
    const isPosMode = pathname?.includes('/pos');

    useEffect(() => {
        import(`@/i18n/dictionaries/${locale}.json`).then(m => setDict(m.default));
    }, [locale]);

    useEffect(() => {
        setMobileMenuOpen(false);
    }, [pathname]);

    if (!dict) {
        return (
            <div className="min-h-screen flex items-center justify-center" style={{ background: 'var(--bg-body)' }}>
                <div className="auth-spinner" />
            </div>
        );
    }

    // ── Full-Screen POS Mode ─────────────────────────────────────────
    if (isPosMode) {
        return (
            <AuthGuard locale={locale}>
                <div className="h-screen overflow-hidden">
                    {children}
                </div>
            </AuthGuard>
        );
    }

    return (
        <AuthGuard locale={locale}>
            <div className="min-h-screen transition-colors duration-300" style={{ background: 'var(--bg-body)' }}>
                {/* Mobile Overlay */}
                {mobileMenuOpen && (
                    <div
                        className="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden"
                        onClick={() => setMobileMenuOpen(false)}
                    />
                )}

                {/* Sidebar */}
                <div className={`
                    fixed inset-y-0 start-0 z-50
                    transition-all duration-300 ease-in-out
                    ${mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
                    ${isRTL && !mobileMenuOpen ? 'translate-x-full lg:translate-x-0' : ''}
                `}>
                    <Sidebar locale={locale as any} dict={dict} />
                </div>

                {/* Main Content */}
                <main className={`min-h-screen transition-all duration-300 ms-0 lg:ms-64`}>
                    {/* Top Bar */}
                    <header
                        className="sticky top-0 z-20 h-16 backdrop-blur-xl flex items-center justify-between px-4 sm:px-8 transition-colors duration-300"
                        style={{ background: 'var(--bg-header)', borderBottom: '1px solid var(--border-default)' }}>

                        {/* Left: Mobile Menu + Search */}
                        <div className="flex items-center gap-3 flex-1">
                            <button
                                onClick={() => setMobileMenuOpen(true)}
                                className="lg:hidden btn-icon"
                            >
                                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                            </button>

                            <div className="relative hidden sm:block">
                                <svg className="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4" style={{ color: 'var(--text-muted)' }} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input
                                    type="text"
                                    placeholder={dict.common?.search || 'Search...'}
                                    className="input-field ps-10 w-72 xl:w-80 py-2 text-sm"
                                />
                            </div>
                        </div>

                        {/* Right: Actions */}
                        <div className="flex items-center gap-3">
                            <button className="w-9 h-9 rounded-xl flex items-center justify-center transition-all relative"
                                style={{ background: 'var(--bg-input)', border: '1px solid var(--border-default)' }}>
                                <svg className="w-4 h-4" style={{ color: 'var(--text-secondary)' }} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span className="absolute -top-0.5 -end-0.5 w-2 h-2 rounded-full bg-red-500" />
                            </button>

                            <div className="flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl transition-colors cursor-pointer"
                                style={{ background: 'var(--bg-surface-hover)' }}>
                                <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold shadow-sm">
                                    {user?.name?.charAt(0) || 'A'}
                                </div>
                                <div className="hidden sm:block">
                                    <p className="text-xs font-semibold leading-tight" style={{ color: 'var(--text-primary)' }}>
                                        {user?.name || 'Admin'}
                                    </p>
                                    <p className="text-[10px]" style={{ color: 'var(--text-muted)' }}>
                                        {user?.role || 'admin'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </header>

                    {/* Page Content */}
                    <div className="p-4 sm:p-6 lg:p-8">{children}</div>
                </main>
            </div>
        </AuthGuard>
    );
}
