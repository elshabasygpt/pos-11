import Link from 'next/link';
import { getDictionary, type Locale } from '@/i18n/config';

export default async function HomePage({
    params,
}: {
    params: { locale: Locale };
}) {
    const dict = await getDictionary(params.locale);
    const isRTL = params.locale === 'ar';

    const features = [
        { icon: '📊', title: isRTL ? 'محاسبة متقدمة' : 'Smart Accounting', desc: isRTL ? 'شجرة حسابات، قيود آلية، ميزان مراجعة، قوائم مالية' : 'Chart of Accounts, Auto Journals, Trial Balance & Financials', gradient: 'from-indigo-500 to-purple-600' },
        { icon: '🏪', title: isRTL ? 'نقاط البيع' : 'POS System', desc: isRTL ? 'فواتير نقدية وآجلة مع ضريبة وخصومات وباركود' : 'Cash & credit invoices with VAT, discounts & barcode', gradient: 'from-emerald-500 to-teal-600' },
        { icon: '📦', title: isRTL ? 'إدارة المخزون' : 'Inventory', desc: isRTL ? 'تعدد مستودعات، باركود، تنبيه مخزون، حركات' : 'Multi-warehouse, barcode, stock alerts & movements', gradient: 'from-amber-500 to-orange-600' },
        { icon: '🧾', title: isRTL ? 'فوترة إلكترونية' : 'e-Invoicing', desc: isRTL ? 'متوافق مع هيئة الزكاة والضريبة (ZATCA)' : 'ZATCA-compliant e-invoicing with QR codes', gradient: 'from-cyan-500 to-blue-600' },
        { icon: '📈', title: isRTL ? 'تقارير مالية' : 'Financial Reports', desc: isRTL ? 'تقارير آنية للمبيعات والأرباح والمصروفات' : 'Real-time sales, profit & expense analytics', gradient: 'from-rose-500 to-pink-600' },
        { icon: '👥', title: isRTL ? 'إدارة العملاء' : 'CRM', desc: isRTL ? 'إدارة العملاء والموردين مع الأرصدة والمعاملات' : 'Manage customers & suppliers with balances', gradient: 'from-violet-500 to-indigo-600' },
    ];

    const stats = [
        { value: '99.9%', label: isRTL ? 'وقت التشغيل' : 'Uptime' },
        { value: '10K+', label: isRTL ? 'فاتورة يومياً' : 'Daily Invoices' },
        { value: '500+', label: isRTL ? 'شركة نشطة' : 'Active Businesses' },
        { value: '24/7', label: isRTL ? 'دعم فني' : 'Support' },
    ];

    return (
        <div className="min-h-screen overflow-hidden" style={{ background: 'var(--bg-body)' }}>
            {/* ─── Navigation ─── */}
            <nav className="fixed top-0 inset-x-0 z-50 backdrop-blur-xl" style={{ background: 'var(--bg-header)', borderBottom: '1px solid var(--border-default)' }}>
                <div className="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="w-9 h-9 rounded-xl flex items-center justify-center"
                            style={{ background: 'rgba(99, 102, 241, 0.15)', border: '1px solid rgba(99, 102, 241, 0.25)' }}>
                            <span className="font-bold text-sm" style={{ color: 'var(--color-primary)' }}>$</span>
                        </div>
                        <span className="font-bold text-sm" style={{ color: 'var(--text-primary)' }}>{dict.common.appName}</span>
                    </div>
                    <div className="flex items-center gap-3">
                        <Link href={isRTL ? '/en' : '/ar'}
                            className="text-xs px-3 py-1.5 rounded-lg"
                            style={{ color: 'var(--text-muted)' }}>
                            {isRTL ? 'English' : 'العربية'}
                        </Link>
                        <Link href={`/${params.locale}/login`}
                            className="text-sm px-5 py-2 rounded-xl font-medium transition-all"
                            style={{ color: 'var(--text-secondary)', border: '1px solid var(--border-default)' }}>
                            {dict.common.login}
                        </Link>
                        <Link href={`/${params.locale}/register`}
                            className="btn-primary text-sm px-5 py-2">
                            {dict.common.register}
                        </Link>
                    </div>
                </div>
            </nav>

            {/* ─── Hero Section ─── */}
            <section className="relative min-h-screen flex items-center pt-16">
                {/* Background Elements */}
                <div className="absolute inset-0 overflow-hidden pointer-events-none">
                    <div className="absolute -top-40 -right-40 w-[500px] h-[500px] bg-indigo-500/8 rounded-full blur-3xl animate-float" />
                    <div className="absolute bottom-20 -left-20 w-[400px] h-[400px] bg-purple-500/6 rounded-full blur-3xl animate-float" style={{ animationDelay: '3s' }} />
                    <div className="absolute top-1/3 left-1/2 w-[300px] h-[300px] bg-cyan-500/5 rounded-full blur-3xl animate-float" style={{ animationDelay: '1.5s' }} />
                    {/* Grid */}
                    <div className="absolute inset-0 opacity-[0.015]"
                        style={{
                            backgroundImage: `radial-gradient(circle, var(--text-muted) 1px, transparent 1px)`,
                            backgroundSize: '40px 40px'
                        }}
                    />
                </div>

                <div className="relative z-10 max-w-7xl mx-auto px-6 w-full">
                    <div className="max-w-3xl mx-auto text-center">
                        {/* Badge */}
                        <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-medium mb-8 animate-fade-in"
                            style={{ background: 'rgba(99, 102, 241, 0.1)', border: '1px solid rgba(99, 102, 241, 0.2)', color: 'var(--color-primary)' }}>
                            <span className="w-2 h-2 rounded-full bg-green-500 animate-pulse" />
                            {isRTL ? 'متوافق مع هيئة ZATCA' : 'ZATCA Compliant'}
                        </div>

                        {/* Title */}
                        <h1 className="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold leading-tight mb-6 animate-slide-up">
                            <span style={{ color: 'var(--text-heading)' }}>
                                {isRTL ? 'أدِر أعمالك' : 'Run your business'}
                            </span>
                            <br />
                            <span className="bg-clip-text text-transparent animate-gradient"
                                style={{ backgroundImage: 'linear-gradient(135deg, #6366f1, #8b5cf6, #a78bfa, #6366f1)', backgroundSize: '200% 200%' }}>
                                {isRTL ? 'بذكاء وكفاءة' : 'smarter & faster'}
                            </span>
                        </h1>

                        <p className="text-lg md:text-xl mb-10 animate-slide-up leading-relaxed max-w-2xl mx-auto" style={{ color: 'var(--text-secondary)', animationDelay: '100ms' }}>
                            {isRTL
                                ? 'نظام محاسبي سحابي متكامل يدير مبيعاتك، مخزونك، وتقاريرك المالية مع التوافق الكامل مع ضريبة القيمة المضافة.'
                                : 'A comprehensive cloud accounting system that manages your sales, inventory, and financial reports with full VAT compliance.'}
                        </p>

                        {/* CTA Buttons */}
                        <div className="flex flex-col sm:flex-row gap-4 justify-center animate-slide-up" style={{ animationDelay: '200ms' }}>
                            <Link href={`/${params.locale}/login`}
                                className="btn-primary text-base px-10 py-4 flex items-center justify-center gap-2">
                                {dict.common.login}
                                <svg className="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </Link>
                            <Link href={`/${params.locale}/register`}
                                className="btn-secondary text-base px-10 py-4">
                                {isRTL ? 'ابدأ مجاناً' : 'Start Free Trial'}
                            </Link>
                        </div>
                    </div>

                    {/* Stats Row */}
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-20 max-w-3xl mx-auto animate-slide-up" style={{ animationDelay: '400ms' }}>
                        {stats.map((s, i) => (
                            <div key={i} className="text-center p-4 rounded-2xl" style={{ background: 'var(--bg-surface)', border: '1px solid var(--border-default)' }}>
                                <p className="text-2xl md:text-3xl font-extrabold" style={{ color: 'var(--color-primary)' }}>{s.value}</p>
                                <p className="text-xs mt-1" style={{ color: 'var(--text-muted)' }}>{s.label}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ─── Features Section ─── */}
            <section className="py-20 md:py-28 relative">
                <div className="max-w-7xl mx-auto px-6">
                    <div className="text-center mb-16">
                        <p className="text-sm font-semibold mb-3" style={{ color: 'var(--color-primary)' }}>
                            {isRTL ? 'المميزات' : 'Features'}
                        </p>
                        <h2 className="text-3xl md:text-4xl font-bold mb-4" style={{ color: 'var(--text-heading)' }}>
                            {isRTL ? 'كل ما تحتاجه في مكان واحد' : 'Everything you need in one place'}
                        </h2>
                        <p className="text-base max-w-2xl mx-auto" style={{ color: 'var(--text-secondary)' }}>
                            {isRTL
                                ? 'أدوات متكاملة لإدارة أعمالك التجارية من المبيعات إلى التقارير المالية'
                                : 'Integrated tools to manage your business from sales to financial statements'}
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {features.map((f, i) => (
                            <div key={i}
                                className="glass-card p-7 group cursor-default"
                                style={{ animationDelay: `${i * 100}ms` }}>
                                <div className={`w-12 h-12 rounded-xl bg-gradient-to-br ${f.gradient} bg-opacity-15 flex items-center justify-center text-2xl mb-5 transition-transform duration-300 group-hover:scale-110`}
                                    style={{ background: `linear-gradient(135deg, rgba(99,102,241,0.12), rgba(139,92,246,0.08))` }}>
                                    {f.icon}
                                </div>
                                <h3 className="text-lg font-bold mb-2" style={{ color: 'var(--text-heading)' }}>{f.title}</h3>
                                <p className="text-sm leading-relaxed" style={{ color: 'var(--text-secondary)' }}>{f.desc}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ─── CTA Section ─── */}
            <section className="py-20 md:py-28 relative">
                <div className="max-w-4xl mx-auto px-6">
                    <div className="relative rounded-3xl p-10 md:p-16 text-center overflow-hidden"
                        style={{
                            background: 'linear-gradient(135deg, #4f46e5, #6366f1, #8b5cf6)',
                        }}>
                        <div className="absolute inset-0 overflow-hidden">
                            <div className="absolute -top-20 -right-20 w-60 h-60 bg-white/10 rounded-full blur-3xl" />
                            <div className="absolute -bottom-20 -left-20 w-60 h-60 bg-white/5 rounded-full blur-3xl" />
                        </div>
                        <div className="relative z-10">
                            <h2 className="text-3xl md:text-4xl font-extrabold text-white mb-4">
                                {isRTL ? 'جاهز لبدء رحلتك المالية؟' : 'Ready to get started?'}
                            </h2>
                            <p className="text-white/70 text-lg mb-8 max-w-xl mx-auto">
                                {isRTL
                                    ? 'ابدأ تجربتك المجانية اليوم واكتشف كيف يمكن لنظامنا تبسيط عملياتك المالية.'
                                    : 'Start your free trial today and discover how our system can simplify your financial operations.'}
                            </p>
                            <div className="flex flex-col sm:flex-row gap-4 justify-center">
                                <Link href={`/${params.locale}/register`}
                                    className="px-10 py-4 rounded-xl bg-white text-indigo-700 font-bold text-base transition-all hover:shadow-lg hover:shadow-white/20 hover:-translate-y-1">
                                    {isRTL ? 'ابدأ مجاناً' : 'Start Free Trial'}
                                </Link>
                                <Link href={`/${params.locale}/login`}
                                    className="px-10 py-4 rounded-xl bg-white/15 text-white font-semibold text-base border border-white/20 transition-all hover:bg-white/25">
                                    {dict.common.login}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* ─── Footer ─── */}
            <footer className="py-10 px-6" style={{ borderTop: '1px solid var(--border-default)' }}>
                <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <div className="w-7 h-7 rounded-lg flex items-center justify-center"
                            style={{ background: 'rgba(99, 102, 241, 0.12)' }}>
                            <span className="text-xs font-bold" style={{ color: 'var(--color-primary)' }}>$</span>
                        </div>
                        <span className="text-sm font-semibold" style={{ color: 'var(--text-primary)' }}>{dict.common.appName}</span>
                    </div>
                    <p className="text-xs" style={{ color: 'var(--text-muted)' }}>
                        © {new Date().getFullYear()} {dict.common.appName}. {isRTL ? 'جميع الحقوق محفوظة.' : 'All rights reserved.'}
                    </p>
                    <div className="flex items-center gap-4">
                        <Link href="/en" className={`text-xs transition-colors ${params.locale === 'en' ? '' : ''}`}
                            style={{ color: params.locale === 'en' ? 'var(--color-primary)' : 'var(--text-muted)' }}>
                            English
                        </Link>
                        <Link href="/ar" className="text-xs font-arabic transition-colors"
                            style={{ color: params.locale === 'ar' ? 'var(--color-primary)' : 'var(--text-muted)' }}>
                            العربية
                        </Link>
                    </div>
                </div>
            </footer>
        </div>
    );
}
