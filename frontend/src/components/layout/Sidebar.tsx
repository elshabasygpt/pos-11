'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { useTheme } from '@/providers/ThemeProvider';
import { logout, getStoredUser } from '@/lib/auth';
import type { Locale } from '@/types';

interface SidebarProps {
    locale: Locale;
    dict: any;
}

const menuItems = [
    { key: 'pos', icon: 'M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2v-3M9 7V5a2 2 0 012-2h6l2 2v8a2 2 0 01-2 2h-2M9 7h2a2 2 0 012 2v2', path: '/pos', labelAr: 'نقطة البيع', labelEn: 'POS', highlight: true },
    { key: 'dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', path: '' },
    { key: 'sales', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', path: '/sales' },
    { key: 'inventory', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', path: '/inventory' },
    { key: 'branches', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', path: '/branches', labelAr: 'الفروع', labelEn: 'Branches' },
    { key: 'transfers', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4', path: '/inventory/transfers', labelAr: 'التحويلات المخزنية', labelEn: 'Stock Transfers' },
    { key: 'stockMovements', icon: 'M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4', path: '/inventory/movements', labelAr: 'حركات المخزون', labelEn: 'Stock Movements' },
    { key: 'returns', icon: 'M9 14l-4-4m0 0l4-4m-4 4h11.586a2 2 0 012 2v2a2 2 0 01-2 2H5', path: '/returns' },
    { key: 'purchases', icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z', path: '/purchases' },
    { key: 'accounting', icon: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z', path: '/accounting' },
    { key: 'reports', icon: 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', path: '/reports', labelAr: 'التقارير الشاملة', labelEn: 'Reports' },
    { key: 'hr', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', path: '/hr', labelAr: 'شؤون الموظفين', labelEn: 'HR & Employees' },
    { key: 'customers', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', path: '/customers' },
    { key: 'suppliers', icon: 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z', path: '/suppliers' },
    { key: 'users', icon: 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z', path: '/users', labelAr: 'المستخدمون', labelEn: 'Users' },
    { key: 'partnerships', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', path: '/partnerships', labelAr: 'الشراكات والأرباح', labelEn: 'Partnerships' },
    { key: 'settings', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', path: '/settings' },
];

const ZATCA_ICON = 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';

export default function Sidebar({ locale, dict }: SidebarProps) {
    const pathname = usePathname();
    const router = useRouter();
    const { theme, toggleTheme } = useTheme();
    const isRTL = locale === 'ar';
    const user = typeof window !== 'undefined' ? getStoredUser() : null;

    const handleLogout = async () => {
        await logout();
        router.push(`/${locale}/login`);
    };

    return (
        <aside
            className="w-64 h-full flex flex-col transition-colors duration-300"
            style={{ background: 'var(--bg-sidebar)', borderInlineEnd: '1px solid var(--border-default)' }}>

            {/* Logo */}
            <div className="h-16 flex items-center gap-3 px-6 flex-shrink-0"
                style={{ borderBottom: '1px solid var(--border-default)' }}>
                <div className="w-9 h-9 rounded-xl flex items-center justify-center"
                    style={{ background: 'rgba(99, 102, 241, 0.15)', border: '1px solid rgba(99, 102, 241, 0.25)' }}>
                    <span className="font-bold text-sm" style={{ color: 'var(--color-primary)' }}>$</span>
                </div>
                <span className="font-bold text-sm" style={{ color: 'var(--text-primary)' }}>{dict.common?.appName || 'SaaS Accounting'}</span>
            </div>

            {/* Navigation */}
            <nav className="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                {menuItems.map((item) => {
                    const href = `/${locale}/dashboard${item.path}`;
                    const isActive =
                        item.path === ''
                            ? pathname === `/${locale}/dashboard`
                            : pathname.startsWith(`/${locale}/dashboard${item.path}`);

                    const label = (item as any).labelAr
                        ? (locale === 'ar' ? (item as any).labelAr : (item as any).labelEn)
                        : ((dict.common as any)?.[item.key] || item.key);

                    return (
                        <Link
                            key={item.key}
                            href={href}
                            className={`sidebar-link ${isActive ? 'active' : ''} ${(item as any).highlight && !isActive ? 'border border-primary-500/20' : ''
                                }`}
                            style={(item as any).highlight && !isActive ? { background: 'rgba(99,102,241,0.06)' } : {}}
                        >
                            <svg
                                className="w-5 h-5 flex-shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={1.5}
                            >
                                <path strokeLinecap="round" strokeLinejoin="round" d={item.icon} />
                            </svg>
                            <span>{label}</span>
                            {(item as any).highlight && (
                                <span className="ms-auto text-[10px] px-1.5 py-0.5 rounded font-bold"
                                    style={{ background: 'rgba(99,102,241,0.2)', color: '#a5b4fc' }}>POS</span>
                            )}
                        </Link>
                    );
                })}

                {/* ZATCA separator */}
                <div className="pt-3 mt-3" style={{ borderTop: '1px solid var(--border-default)' }}>
                    <p className="px-3 text-xs font-semibold uppercase tracking-wider mb-1.5"
                        style={{ color: 'var(--text-muted)' }}>
                        {isRTL ? 'الضريبة' : 'Tax'}
                    </p>
                    <Link
                        href={`/${locale}/dashboard/zatca/vat-report`}
                        className={`sidebar-link ${pathname.includes('/zatca') ? 'active' : ''}`}
                    >
                        <svg className="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                            <path strokeLinecap="round" strokeLinejoin="round" d={ZATCA_ICON} />
                        </svg>
                        <span>{(dict.zatca?.vatReport) || (isRTL ? 'تقرير ضريبة القيمة المضافة' : 'VAT Report')}</span>
                        <span className="ms-auto text-[10px] px-1.5 py-0.5 rounded font-semibold"
                            style={{ background: 'rgba(16,185,129,0.12)', color: '#10b981' }}>ZATCA</span>
                    </Link>
                </div>
            </nav>

            {/* Bottom Controls */}
            <div className="px-3 py-4 space-y-3 flex-shrink-0" style={{ borderTop: '1px solid var(--border-default)' }}>
                {/* Theme Toggle */}
                <button
                    onClick={toggleTheme}
                    className="w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-300 cursor-pointer"
                    style={{ background: 'var(--bg-surface-hover)', color: 'var(--text-secondary)' }}
                >
                    <div className="flex items-center gap-2">
                        {theme === 'dark' ? (
                            <svg className="w-4 h-4 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        ) : (
                            <svg className="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        )}
                        <span>{theme === 'dark' ? (isRTL ? 'الوضع النهاري' : 'Light Mode') : (isRTL ? 'الوضع الليلي' : 'Dark Mode')}</span>
                    </div>
                    <div className={`w-10 h-5 rounded-full flex items-center transition-all duration-300 px-0.5 ${theme === 'dark' ? 'bg-primary-600 justify-end' : 'bg-surface-200 justify-start'}`}>
                        <div className="w-4 h-4 rounded-full bg-white shadow-sm transition-all duration-300" />
                    </div>
                </button>

                {/* Language Toggle */}
                <div className="flex gap-2">
                    <Link
                        href={`/en/dashboard`}
                        className={`flex-1 text-center text-xs py-2 rounded-lg transition-all ${locale === 'en' ? 'bg-primary-600/20 text-primary-400 font-semibold' : 'hover:opacity-80'}`}
                        style={locale !== 'en' ? { color: 'var(--text-muted)' } : {}}
                    >
                        EN
                    </Link>
                    <Link
                        href={`/ar/dashboard`}
                        className={`flex-1 text-center text-xs py-2 rounded-lg transition-all font-arabic ${locale === 'ar' ? 'bg-primary-600/20 text-primary-400 font-semibold' : 'hover:opacity-80'}`}
                        style={locale !== 'ar' ? { color: 'var(--text-muted)' } : {}}
                    >
                        عربي
                    </Link>
                </div>

                {/* Logout Button */}
                <button
                    onClick={handleLogout}
                    className="w-full flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
                    style={{ color: '#ef4444', background: 'rgba(239, 68, 68, 0.06)' }}
                >
                    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                    </svg>
                    <span>{isRTL ? 'تسجيل الخروج' : 'Sign Out'}</span>
                </button>
            </div>
        </aside>
    );
}
