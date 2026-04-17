'use client';

import { useState, useEffect } from 'react';
import { useParams } from 'next/navigation';
import { settingsApi } from '@/lib/api';
import ZatcaSettingsSection from '@/components/settings/ZatcaSettingsSection';

export default function SettingsPage() {
    const params = useParams();
    const locale = (params?.locale as string) || 'ar';
    const isRTL = locale === 'ar';
    const [dict, setDict] = useState<any>(null);
    const [saving, setSaving] = useState(false);
    const [saved, setSaved] = useState(false);
    const [form, setForm] = useState({
        company_name: '',
        phone: '',
        email: '',
        website: '',
    });

    useEffect(() => {
        import(`@/i18n/dictionaries/${locale}.json`).then(m => setDict(m.default));
    }, [locale]);

    // Load settings from API, fallback to defaults
    useEffect(() => {
        settingsApi.getSettings()
            .then(res => {
                const data = res.data?.data || res.data || {};
                setForm({
                    company_name: data.company_name || (isRTL ? 'شركتي التجارية' : 'My Trading Company'),
                    phone: data.phone || '+966 11 000 0000',
                    email: data.email || 'info@company.com',
                    website: data.website || 'www.company.com',
                });
            })
            .catch(() => {
                // Backend not available — use defaults
                setForm({
                    company_name: isRTL ? 'شركتي التجارية' : 'My Trading Company',
                    phone: '+966 11 000 0000',
                    email: 'info@company.com',
                    website: 'www.company.com',
                });
            });
    }, [isRTL]);

    const handleSave = async () => {
        setSaving(true);
        setSaved(false);
        try {
            await settingsApi.updateSettings(form);
            setSaved(true);
            setTimeout(() => setSaved(false), 3000);
        } catch {
            alert(isRTL ? 'حدث خطأ أثناء حفظ الإعدادات' : 'Error saving settings');
        } finally {
            setSaving(false);
        }
    };

    if (!dict) {
        return (
            <div className="min-h-[50vh] flex items-center justify-center">
                <div className="auth-spinner" />
            </div>
        );
    }

    return (
        <div className="space-y-6 animate-fade-in max-w-4xl">
            <div>
                <h1 className="text-2xl font-bold" style={{ color: 'var(--text-heading)' }}>{dict.common.settings}</h1>
                <p className="text-sm mt-1" style={{ color: 'var(--text-muted)' }}>
                    {isRTL ? 'إدارة إعدادات الحساب والنظام' : 'Manage account and system settings'}
                </p>
            </div>

            <div className="space-y-5">
                {/* ── Company Info ── */}
                <div className="glass-card p-6">
                    <h3 className="text-lg font-semibold mb-4" style={{ color: 'var(--text-heading)' }}>
                        {isRTL ? 'معلومات الشركة' : 'Company Information'}
                    </h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium mb-1.5" style={{ color: 'var(--text-secondary)' }}>
                                {isRTL ? 'اسم الشركة' : 'Company Name'}
                            </label>
                            <input
                                type="text"
                                className="input-field w-full"
                                value={form.company_name}
                                onChange={e => setForm({ ...form, company_name: e.target.value })}
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1.5" style={{ color: 'var(--text-secondary)' }}>
                                {isRTL ? 'رقم الهاتف' : 'Phone Number'}
                            </label>
                            <input
                                type="text"
                                className="input-field w-full"
                                value={form.phone}
                                onChange={e => setForm({ ...form, phone: e.target.value })}
                                dir="ltr"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1.5" style={{ color: 'var(--text-secondary)' }}>
                                {isRTL ? 'البريد الإلكتروني' : 'Email'}
                            </label>
                            <input
                                type="email"
                                className="input-field w-full"
                                value={form.email}
                                onChange={e => setForm({ ...form, email: e.target.value })}
                                dir="ltr"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1.5" style={{ color: 'var(--text-secondary)' }}>
                                {isRTL ? 'الموقع الإلكتروني' : 'Website'}
                            </label>
                            <input
                                type="text"
                                className="input-field w-full"
                                value={form.website}
                                onChange={e => setForm({ ...form, website: e.target.value })}
                                dir="ltr"
                            />
                        </div>
                    </div>
                    <div className="flex items-center gap-3 mt-4">
                        <button className="btn-primary" onClick={handleSave} disabled={saving}>
                            {saving ? (isRTL ? 'جاري الحفظ...' : 'Saving...') : dict.common.save}
                        </button>
                        {saved && (
                            <span className="text-sm font-medium text-green-500 animate-fade-in flex items-center gap-1">
                                ✓ {isRTL ? 'تم الحفظ بنجاح' : 'Saved successfully'}
                            </span>
                        )}
                    </div>
                </div>

                {/* ── ZATCA / e-Invoicing ── */}
                <ZatcaSettingsSection dict={dict} locale={locale as any} />

                {/* ── Language ── */}
                <div className="glass-card p-6">
                    <h3 className="text-lg font-semibold mb-4" style={{ color: 'var(--text-heading)' }}>{dict.common.language}</h3>
                    <div className="flex gap-4">
                        <a href="/en/dashboard/settings" className={`px-6 py-3 rounded-xl border text-sm font-medium transition-all ${locale === 'en' ? 'bg-primary-600/20 border-primary-500/30 text-primary-400' : ''}`}
                            style={locale !== 'en' ? { borderColor: 'var(--border-default)', color: 'var(--text-secondary)' } : {}}>
                            🇬🇧 {dict.common.english}
                        </a>
                        <a href="/ar/dashboard/settings" className={`px-6 py-3 rounded-xl border text-sm font-medium transition-all font-arabic ${locale === 'ar' ? 'bg-primary-600/20 border-primary-500/30 text-primary-400' : ''}`}
                            style={locale !== 'ar' ? { borderColor: 'var(--border-default)', color: 'var(--text-secondary)' } : {}}>
                            🇸🇦 {dict.common.arabic}
                        </a>
                    </div>
                </div>

                {/* ── Subscription ── */}
                <div className="glass-card p-6">
                    <h3 className="text-lg font-semibold mb-4" style={{ color: 'var(--text-heading)' }}>{isRTL ? 'الاشتراك' : 'Subscription'}</h3>
                    <div className="flex items-center justify-between p-4 rounded-xl" style={{ background: 'var(--bg-surface-secondary)', border: '1px solid var(--border-light)' }}>
                        <div>
                            <p className="font-semibold" style={{ color: 'var(--text-primary)' }}>{isRTL ? 'الخطة الاحترافية' : 'Professional Plan'}</p>
                            <p className="text-sm" style={{ color: 'var(--text-muted)' }}>{isRTL ? 'اشتراك نشط' : 'Active subscription'}</p>
                        </div>
                        <span className="badge badge-success">{isRTL ? 'نشط' : 'Active'}</span>
                    </div>
                </div>
            </div>
        </div>
    );
}
