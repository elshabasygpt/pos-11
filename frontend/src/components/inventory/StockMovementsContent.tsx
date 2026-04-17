'use client';

import { useState, useMemo } from 'react';

interface StockMovementsContentProps {
    dict: any;
    locale: string;
}

type MovementType = 'incoming' | 'outgoing' | 'adjustment' | 'return';

const PRODUCTS_LIST = [
    { id: 1, code: 'ELC-001', name: 'Samsung TV 55"', nameAr: 'شاشة سامسونج 55"', stock: 12, reorder: 5 },
    { id: 2, code: 'PH-001', name: 'iPhone 15 Pro', nameAr: 'آيفون 15 برو', stock: 3, reorder: 5 },
    { id: 3, code: 'PH-002', name: 'Samsung Galaxy S24', nameAr: 'سامسونج جالكسي S24', stock: 15, reorder: 10 },
    { id: 4, code: 'PC-001', name: 'HP LaserJet Printer', nameAr: 'طابعة HP ليزر', stock: 2, reorder: 3 },
    { id: 5, code: 'ACC-001', name: 'USB-C Cable 2m', nameAr: 'كابل USB-C 2م', stock: 200, reorder: 50 },
    { id: 6, code: 'OFC-001', name: 'Office Chair', nameAr: 'كرسي مكتب', stock: 20, reorder: 10 },
];

const MOVEMENTS_DATA = [
    { id: 1, date: '2026-03-01', type: 'incoming' as MovementType, product: 'Samsung TV 55"', productAr: 'شاشة سامسونج 55"', code: 'ELC-001', qty: 10, before: 2, after: 12, reason: 'شراء من المورد', user: 'أحمد', ref: 'PO-2024-001' },
    { id: 2, date: '2026-03-01', type: 'outgoing' as MovementType, product: 'iPhone 15 Pro', productAr: 'آيفون 15 برو', code: 'PH-001', qty: 2, before: 5, after: 3, reason: 'فاتورة بيع', user: 'سارة', ref: 'INV-2024-0045' },
    { id: 3, date: '2026-02-29', type: 'outgoing' as MovementType, product: 'USB-C Cable 2m', productAr: 'كابل USB-C 2م', code: 'ACC-001', qty: 15, before: 215, after: 200, reason: 'فاتورة بيع', user: 'علي', ref: 'INV-2024-0044' },
    { id: 4, date: '2026-02-28', type: 'adjustment' as MovementType, product: 'HP LaserJet Printer', productAr: 'طابعة HP ليزر', code: 'PC-001', qty: -1, before: 3, after: 2, reason: 'تالف / كسر', user: 'مدير', ref: 'ADJ-2024-003' },
    { id: 5, date: '2026-02-28', type: 'return' as MovementType, product: 'Samsung Galaxy S24', productAr: 'سامسونج جالكسي S24', code: 'PH-002', qty: 1, before: 14, after: 15, reason: 'إرجاع من العميل', user: 'ريم', ref: 'RET-2024-002' },
    { id: 6, date: '2026-02-27', type: 'incoming' as MovementType, product: 'Office Chair', productAr: 'كرسي مكتب', code: 'OFC-001', qty: 5, before: 15, after: 20, reason: 'شراء من المورد', user: 'أحمد', ref: 'PO-2024-002' },
    { id: 7, date: '2026-02-26', type: 'outgoing' as MovementType, product: 'Samsung TV 55"', productAr: 'شاشة سامسونج 55"', code: 'ELC-001', qty: 3, before: 15, after: 12, reason: 'فاتورة بيع', user: 'سارة', ref: 'INV-2024-0040' },
    { id: 8, date: '2026-02-25', type: 'adjustment' as MovementType, product: 'USB-C Cable 2m', productAr: 'كابل USB-C 2م', code: 'ACC-001', qty: 5, before: 210, after: 215, reason: 'تسوية مخزون', user: 'مدير', ref: 'ADJ-2024-002' },
];

const TYPE_CONFIG = {
    incoming: { ar: 'وارد', en: 'Incoming', icon: '⬆️', color: '#10b981', bg: 'rgba(16,185,129,0.12)' },
    outgoing: { ar: 'صادر', en: 'Outgoing', icon: '⬇️', color: '#ef4444', bg: 'rgba(239,68,68,0.12)' },
    adjustment: { ar: 'تسوية', en: 'Adjustment', icon: '⚖️', color: '#f59e0b', bg: 'rgba(245,158,11,0.12)' },
    return: { ar: 'مرتجع', en: 'Return', icon: '↩️', color: '#6366f1', bg: 'rgba(99,102,241,0.12)' },
};

export default function StockMovementsContent({ dict, locale }: StockMovementsContentProps) {
    const isRTL = locale === 'ar';

    const [typeFilter, setTypeFilter] = useState<MovementType | 'all'>('all');
    const [search, setSearch] = useState('');
    const [showAddModal, setShowAddModal] = useState(false);
    const [movements, setMovements] = useState(MOVEMENTS_DATA);

    // Add movement form
    const [form, setForm] = useState({ productId: '', type: 'incoming' as MovementType, qty: '', reason: '', ref: '' });

    const lowStockProducts = PRODUCTS_LIST.filter((p) => p.stock <= p.reorder);

    const filtered = useMemo(() => {
        return movements.filter((m) => {
            const matchType = typeFilter === 'all' || m.type === typeFilter;
            const q = search.toLowerCase();
            const matchSearch = !q || m.product.toLowerCase().includes(q) || m.productAr.includes(q) || m.code.toLowerCase().includes(q) || m.ref.toLowerCase().includes(q);
            return matchType && matchSearch;
        });
    }, [movements, typeFilter, search]);

    // Summary stats
    const totalIn = movements.filter((m) => m.type === 'incoming').reduce((s, m) => s + m.qty, 0);
    const totalOut = movements.filter((m) => m.type === 'outgoing').reduce((s, m) => s + m.qty, 0);
    const totalAdj = movements.filter((m) => m.type === 'adjustment').length;
    const totalRet = movements.filter((m) => m.type === 'return').reduce((s, m) => s + m.qty, 0);

    const handleAddMovement = () => {
        const product = PRODUCTS_LIST.find((p) => p.id === parseInt(form.productId));
        if (!product || !form.qty) return;
        const qty = parseInt(form.qty) * (form.type === 'outgoing' ? -1 : 1);
        const before = product.stock;
        const after = before + qty;
        const newMovement = {
            id: movements.length + 1,
            date: new Date().toISOString().slice(0, 10),
            type: form.type,
            product: product.name,
            productAr: product.nameAr,
            code: product.code,
            qty: parseInt(form.qty),
            before,
            after,
            reason: form.reason || (isRTL ? 'حركة يدوية' : 'Manual movement'),
            user: isRTL ? 'مستخدم' : 'User',
            ref: form.ref || `MAN-${Date.now()}`,
        };
        setMovements([newMovement, ...movements]);
        setForm({ productId: '', type: 'incoming', qty: '', reason: '', ref: '' });
        setShowAddModal(false);
    };

    return (
        <div className="space-y-6 animate-fade-in">
            {/* Header */}
            <div className="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-white flex items-center gap-2">
                        <span>📦</span> {isRTL ? 'حركات المخزون' : 'Stock Movements'}
                    </h1>
                    <p className="text-sm mt-1" style={{ color: 'var(--text-muted)' }}>
                        {isRTL ? 'تتبع وارد وصادر المخزون' : 'Track inventory in and out'}
                    </p>
                </div>
                <div className="flex gap-3">
                    <a href={`/${locale}/dashboard/inventory`} className="btn-secondary text-sm flex items-center gap-2">
                        ← {isRTL ? 'المخزون' : 'Inventory'}
                    </a>
                    <button onClick={() => setShowAddModal(true)} className="btn-primary flex items-center gap-2">
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        {isRTL ? 'إضافة حركة' : 'Add Movement'}
                    </button>
                </div>
            </div>

            {/* Low Stock Alert */}
            {lowStockProducts.length > 0 && (
                <div className="p-4 rounded-xl flex items-start gap-3" style={{ background: 'rgba(239,68,68,0.08)', border: '1px solid rgba(239,68,68,0.25)' }}>
                    <span className="text-xl mt-0.5">⚠️</span>
                    <div className="flex-1">
                        <p className="font-semibold text-red-400 text-sm mb-1">
                            {isRTL ? `تنبيه: ${lowStockProducts.length} منتجات تحت حد الطلب` : `Alert: ${lowStockProducts.length} products below reorder point`}
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {lowStockProducts.map((p) => (
                                <span key={p.id} className="text-xs px-2 py-0.5 rounded-full" style={{ background: 'rgba(239,68,68,0.12)', color: '#fca5a5' }}>
                                    {isRTL ? p.nameAr : p.name} — {isRTL ? 'الكمية:' : 'Qty:'} {p.stock}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>
            )}

            {/* Summary Cards */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                {[
                    { type: 'incoming', label: isRTL ? 'إجمالي الوارد' : 'Total Incoming', value: totalIn, suffix: isRTL ? 'وحدة' : 'units' },
                    { type: 'outgoing', label: isRTL ? 'إجمالي الصادر' : 'Total Outgoing', value: totalOut, suffix: isRTL ? 'وحدة' : 'units' },
                    { type: 'adjustment', label: isRTL ? 'عمليات التسوية' : 'Adjustments', value: totalAdj, suffix: isRTL ? 'عملية' : 'ops' },
                    { type: 'return', label: isRTL ? 'إجمالي المرتجعات' : 'Total Returns', value: totalRet, suffix: isRTL ? 'وحدة' : 'units' },
                ].map((card) => {
                    const cfg = TYPE_CONFIG[card.type as MovementType];
                    return (
                        <div key={card.type} className="stat-card cursor-pointer" onClick={() => setTypeFilter(card.type as MovementType)}>
                            <div className="relative flex items-start justify-between">
                                <div>
                                    <p className="text-xs mb-1" style={{ color: 'var(--text-secondary)' }}>{card.label}</p>
                                    <p className="text-2xl font-bold" style={{ color: cfg.color }}>{card.value}</p>
                                    <p className="text-xs mt-1" style={{ color: 'var(--text-muted)' }}>{card.suffix}</p>
                                </div>
                                <span className="text-2xl">{cfg.icon}</span>
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Table */}
            <div className="glass-card p-6">
                {/* Filters */}
                <div className="flex flex-wrap items-center gap-3 mb-5">
                    <h3 className="font-semibold text-white flex-1">{isRTL ? 'سجل الحركات' : 'Movement Log'} <span className="text-xs font-normal ms-2 px-2 py-0.5 rounded-full" style={{ background: 'var(--bg-input)', color: 'var(--text-muted)' }}>{filtered.length}</span></h3>
                    <div className="flex gap-1.5 flex-wrap">
                        {(['all', 'incoming', 'outgoing', 'adjustment', 'return'] as const).map((t) => {
                            const cfg = t === 'all' ? null : TYPE_CONFIG[t];
                            return (
                                <button
                                    key={t}
                                    onClick={() => setTypeFilter(t)}
                                    className="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                                    style={typeFilter === t
                                        ? { background: t === 'all' ? 'var(--color-primary)' : cfg!.bg, color: t === 'all' ? '#fff' : cfg!.color, border: `1px solid ${t === 'all' ? 'transparent' : cfg!.color + '40'}` }
                                        : { background: 'var(--bg-input)', color: 'var(--text-muted)', border: '1px solid var(--border-default)' }
                                    }
                                >
                                    {t === 'all' ? (isRTL ? 'الكل' : 'All') : (isRTL ? cfg!.ar : cfg!.en)}
                                </button>
                            );
                        })}
                    </div>
                    <div className="relative">
                        <svg className="absolute start-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5" style={{ color: 'var(--text-muted)' }} fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input type="text" placeholder={isRTL ? 'بحث...' : 'Search...'} value={search} onChange={(e) => setSearch(e.target.value)} className="input-field ps-9 py-1.5 text-sm w-44" />
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="data-table text-sm">
                        <thead>
                            <tr>
                                <th>{isRTL ? 'التاريخ' : 'Date'}</th>
                                <th>{isRTL ? 'النوع' : 'Type'}</th>
                                <th>{isRTL ? 'المنتج' : 'Product'}</th>
                                <th>{isRTL ? 'الكمية' : 'Qty'}</th>
                                <th>{isRTL ? 'قبل' : 'Before'}</th>
                                <th>{isRTL ? 'بعد' : 'After'}</th>
                                <th>{isRTL ? 'السبب' : 'Reason'}</th>
                                <th>{isRTL ? 'المرجع' : 'Reference'}</th>
                                <th>{isRTL ? 'المستخدم' : 'User'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filtered.map((m) => {
                                const cfg = TYPE_CONFIG[m.type];
                                return (
                                    <tr key={m.id}>
                                        <td style={{ color: 'var(--text-muted)' }}>{m.date}</td>
                                        <td>
                                            <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold" style={{ background: cfg.bg, color: cfg.color }}>
                                                {cfg.icon} {isRTL ? cfg.ar : cfg.en}
                                            </span>
                                        </td>
                                        <td>
                                            <span className="font-medium" style={{ color: 'var(--text-primary)' }}>{isRTL ? m.productAr : m.product}</span>
                                            <span className="block text-[11px] font-mono" style={{ color: 'var(--text-muted)' }}>{m.code}</span>
                                        </td>
                                        <td>
                                            <span className="font-bold" style={{ color: m.type === 'outgoing' ? '#ef4444' : m.type === 'incoming' || m.type === 'return' ? '#10b981' : '#f59e0b' }}>
                                                {m.type === 'outgoing' ? '-' : '+'}{m.qty}
                                            </span>
                                        </td>
                                        <td style={{ color: 'var(--text-secondary)' }}>{m.before}</td>
                                        <td>
                                            <span className="font-semibold" style={{ color: m.after <= 5 ? '#f59e0b' : 'var(--text-primary)' }}>
                                                {m.after}
                                                {m.after <= 5 && <span className="ms-1 text-[10px]">⚠️</span>}
                                            </span>
                                        </td>
                                        <td style={{ color: 'var(--text-secondary)' }}>{m.reason}</td>
                                        <td>
                                            <span className="text-primary-400 font-mono text-[11px]">{m.ref}</span>
                                        </td>
                                        <td style={{ color: 'var(--text-muted)' }}>{m.user}</td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                    {filtered.length === 0 && (
                        <div className="text-center py-10">
                            <span className="text-3xl mb-2 block">📭</span>
                            <p style={{ color: 'var(--text-muted)' }}>{isRTL ? 'لا توجد حركات' : 'No movements found'}</p>
                        </div>
                    )}
                </div>
            </div>

            {/* Add Movement Modal */}
            {showAddModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4" style={{ background: 'rgba(0,0,0,0.8)', backdropFilter: 'blur(8px)' }}>
                    <div className="w-full max-w-md rounded-2xl overflow-hidden" style={{ background: 'var(--bg-surface)', border: '1px solid var(--border-default)' }}>
                        <div className="px-6 py-4 flex items-center justify-between" style={{ borderBottom: '1px solid var(--border-default)' }}>
                            <h2 className="text-lg font-bold text-white flex items-center gap-2">
                                <span>📦</span> {isRTL ? 'إضافة حركة مخزون' : 'Add Stock Movement'}
                            </h2>
                            <button onClick={() => setShowAddModal(false)} className="btn-icon">
                                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <div className="p-6 space-y-4">
                            <div>
                                <label className="block text-sm font-medium mb-1.5" style={{ color: 'var(--text-secondary)' }}>{isRTL ? 'المنتج' : 'Product'} *</label>
                                <select className="input-field w-full" value={form.productId} onChange={(e) => setForm({ ...form, productId: e.target.value })}>
                                    <option value="">{isRTL ? 'اختر منتجاً' : 'Select product'}</option>
                                    {PRODUCTS_LIST.map((p) => (
                                        <option key={p.id} value={p.id}>{isRTL ? p.nameAr : p.name} ({isRTL ? 'الكمية الحالية:' : 'Stock:'} {p.stock})</option>
                                    ))}
                                </select>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium mb-1.5" style={{ color: 'var(--text-secondary)' }}>{isRTL ? 'نوع الحركة' : 'Movement Type'} *</label>
                                    <select className="input-field w-full" value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value as MovementType })}>
                                        {(Object.entries(TYPE_CONFIG) as [MovementType, typeof TYPE_CONFIG[MovementType]][]).map(([key, cfg]) => (
                                            <option key={key} value={key}>{cfg.icon} {isRTL ? cfg.ar : cfg.en}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1.5" style={{ color: 'var(--text-secondary)' }}>{isRTL ? 'الكمية' : 'Quantity'} *</label>
                                    <input type="number" min="1" className="input-field w-full" placeholder="0" value={form.qty} onChange={(e) => setForm({ ...form, qty: e.target.value })} />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-1.5" style={{ color: 'var(--text-secondary)' }}>{isRTL ? 'السبب' : 'Reason'}</label>
                                <input type="text" className="input-field w-full" placeholder={isRTL ? 'سبب الحركة...' : 'Reason for movement...'} value={form.reason} onChange={(e) => setForm({ ...form, reason: e.target.value })} />
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-1.5" style={{ color: 'var(--text-secondary)' }}>{isRTL ? 'رقم المرجع' : 'Reference #'}</label>
                                <input type="text" className="input-field w-full" placeholder="PO-XXXX / INV-XXXX" value={form.ref} onChange={(e) => setForm({ ...form, ref: e.target.value })} dir="ltr" />
                            </div>
                        </div>
                        <div className="px-6 py-4 flex gap-3 justify-end" style={{ borderTop: '1px solid var(--border-default)' }}>
                            <button onClick={() => setShowAddModal(false)} className="btn-secondary">{isRTL ? 'إلغاء' : 'Cancel'}</button>
                            <button onClick={handleAddMovement} className="btn-primary" disabled={!form.productId || !form.qty}>
                                {isRTL ? 'حفظ الحركة' : 'Save Movement'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
