"use client";

import React, { useState, useEffect } from 'react';
import { useLanguage } from '@/i18n/LanguageContext';
import { hrApi } from '@/lib/api';

export default function PayrollPage() {
    const { d, isRTL } = useLanguage();
    const [payrolls, setPayrolls] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [month, setMonth] = useState(new Date().getMonth() + 1);
    const [year, setYear] = useState(new Date().getFullYear());

    useEffect(() => {
        fetchData();
    }, [month, year]);

    const fetchData = async () => {
        setLoading(true);
        try {
            const res = await hrApi.getPayrolls({ month, year, limit: 100 });
            setPayrolls(res.data?.data || []);
        } catch (error) {
            console.error("Failed to fetch payrolls", error);
        } finally {
            setLoading(false);
        }
    };

    const handleGenerate = async () => {
        try {
            setLoading(true);
            await hrApi.generatePayroll({ month, year });
            await fetchData();
            alert("Payrolls generated successfully!");
        } catch (error: any) {
            alert(error.response?.data?.message || "Failed to generate payrolls");
            setLoading(false);
        }
    };

    const handlePay = async (id: string, empName: string) => {
        if (!confirm(`Are you sure you want to mark ${empName}'s salary as paid? This will create an expense record and deduct from company balance.`)) return;
        
        try {
            await hrApi.markPayrollAsPaid(id);
            fetchData();
        } catch (error: any) {
            alert(error.response?.data?.message || "Failed to mark as paid");
        }
    };

    const totalSalary = payrolls.reduce((sum, p) => sum + parseFloat(p.net_salary), 0);

    return (
        <div className="p-4 sm:p-6 space-y-6">
            {/* Header */}
            <div className="flex flex-col md:flex-row justify-between md:items-center bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 gap-4">
                <div>
                    <h1 className="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-rose-600">
                        {d.hr?.payroll || 'Payroll Management'}
                    </h1>
                    <p className="text-slate-500 mt-1">Manage and pay employee salaries</p>
                </div>
                
                <div className="flex gap-3 items-center">
                    <select 
                        value={month} 
                        onChange={(e) => setMonth(parseInt(e.target.value))}
                        className="p-2 border rounded-lg bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                    >
                        {Array.from({length: 12}, (_, i) => i + 1).map(m => (
                            <option key={m} value={m}>{new Date(2000, m - 1, 1).toLocaleString('en-US', { month: 'long' })} ({m})</option>
                        ))}
                    </select>
                    <input 
                        type="number" 
                        value={year} 
                        onChange={(e) => setYear(parseInt(e.target.value))}
                        className="p-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 w-24 text-center"
                    />
                    <button
                        onClick={handleGenerate}
                        className="px-4 py-2 bg-slate-800 dark:bg-slate-700 text-white rounded-lg hover:bg-slate-900 transition flex items-center gap-2"
                        title="Calculate deductions and bonuses based on attendance"
                    >
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                        {d.hr?.generatePayroll || 'Generate Payroll'}
                    </button>
                </div>
            </div>

            {/* Summary Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <p className="text-slate-500 text-sm font-medium uppercase">{d.common?.total || 'Total'} Amount</p>
                    <h3 className="text-3xl font-bold mt-2 text-pink-600">{totalSalary.toFixed(2)}</h3>
                </div>
                <div className="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <p className="text-slate-500 text-sm font-medium uppercase">Paid</p>
                    <h3 className="text-3xl font-bold mt-2 text-green-600">
                        {payrolls.filter(p => p.status === 'paid').reduce((s, p) => s + parseFloat(p.net_salary), 0).toFixed(2)}
                    </h3>
                </div>
                <div className="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
                    <p className="text-slate-500 text-sm font-medium uppercase">Pending Drafts</p>
                    <h3 className="text-3xl font-bold mt-2 text-orange-500">
                        {payrolls.filter(p => p.status === 'draft').reduce((s, p) => s + parseFloat(p.net_salary), 0).toFixed(2)}
                    </h3>
                </div>
            </div>

            {/* Table */}
            {loading ? (
                <div className="text-center py-10">Loading...</div>
            ) : (
                <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <table className="w-full text-sm text-left">
                        <thead className="text-xs text-slate-500 uppercase bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700" style={{ textAlign: isRTL ? 'right' : 'left' }}>
                            <tr>
                                <th className="px-6 py-4">{d.hr?.employeeName || 'Name'}</th>
                                <th className="px-6 py-4">{d.hr?.baseSalary || 'Base Salary'}</th>
                                <th className="px-6 py-4">{d.hr?.deductions || 'Deductions'}</th>
                                <th className="px-6 py-4">{d.hr?.bonuses || 'Bonuses'}</th>
                                <th className="px-6 py-4 text-pink-600">{d.hr?.netSalary || 'Net Salary'}</th>
                                <th className="px-6 py-4">{d.hr?.status || 'Status'}</th>
                                <th className="px-6 py-4 text-center">{d.hr?.actions || 'Actions'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {payrolls.map(pr => (
                                <tr key={pr.id} className="border-b border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                    <td className="px-6 py-4 font-medium" style={{ textAlign: isRTL ? 'right' : 'left' }}>{pr.employee?.name}</td>
                                    <td className="px-6 py-4" style={{ textAlign: isRTL ? 'right' : 'left' }}>{parseFloat(pr.base_salary).toFixed(2)}</td>
                                    <td className="px-6 py-4 text-red-500" style={{ textAlign: isRTL ? 'right' : 'left' }}>{parseFloat(pr.deductions).toFixed(2)}</td>
                                    <td className="px-6 py-4 text-green-500" style={{ textAlign: isRTL ? 'right' : 'left' }}>{parseFloat(pr.bonuses).toFixed(2)}</td>
                                    <td className="px-6 py-4 font-bold text-pink-600" style={{ textAlign: isRTL ? 'right' : 'left' }}>{parseFloat(pr.net_salary).toFixed(2)}</td>
                                    <td className="px-6 py-4" style={{ textAlign: isRTL ? 'right' : 'left' }}>
                                        <span className={`px-2 py-1 rounded text-xs ${pr.status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-800 font-bold'}`}>
                                            {d.hr?.[pr.status] || pr.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-center">
                                        {pr.status === 'draft' && (
                                            <button 
                                                onClick={() => handlePay(pr.id, pr.employee?.name)}
                                                className="px-3 py-1.5 bg-green-500 text-white rounded hover:bg-green-600 text-xs font-bold shadow-sm"
                                            >
                                                {d.hr?.markPaid || 'Pay Salary'}
                                            </button>
                                        )}
                                        {pr.status === 'paid' && (
                                            <span className="text-green-600 text-xs font-medium px-3 py-1.5 border border-green-200 rounded bg-green-50">Expense Recorded</span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {payrolls.length === 0 && !loading && (
                                <tr>
                                    <td colSpan={7} className="text-center py-6 text-slate-500">No payroll generated for this month. click "Generate Payroll".</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
