"use client";

import React, { useState, useEffect } from 'react';
import { useLanguage } from '@/i18n/LanguageContext';
import { hrApi, usersApi } from '@/lib/api';

export default function EmployeesPage() {
    const { d, isRTL } = useLanguage();
    const [employees, setEmployees] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [formData, setFormData] = useState<any>({
        name: '', position: '', phone: '', base_salary: 0, shift_start: '09:00', shift_end: '17:00', is_active: true, user_id: ''
    });
    
    // Quick fetch users if we want to link employee to a system user
    const [users, setUsers] = useState<any[]>([]);

    useEffect(() => {
        fetchData();
        fetchUsers();
    }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const res = await hrApi.getEmployees({ limit: 100 });
            setEmployees(res.data?.data || []);
        } catch (error) {
            console.error("Failed to fetch employees", error);
        } finally {
            setLoading(false);
        }
    };

    const fetchUsers = async () => {
        try {
            const res = await usersApi.getUsers({ limit: 50 });
            setUsers(res.data?.data || []);
        } catch (error) {
            console.error("Failed to fetch users", error);
        }
    };

    const handleSave = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            if (formData.id) {
                await hrApi.updateEmployee(formData.id, formData);
            } else {
                await hrApi.createEmployee(formData);
            }
            setIsModalOpen(false);
            fetchData();
        } catch (error) {
            alert("Error saving employee");
        }
    };

    const handleEdit = (emp: any) => {
        setFormData({ ...emp });
        setIsModalOpen(true);
    };

    return (
        <div className="p-4 sm:p-6 space-y-6">
            <div className="flex justify-between items-center bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
                <div>
                    <h1 className="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-pink-500 to-rose-600">
                        {d.hr?.employees || 'Employees'}
                    </h1>
                    <p className="text-slate-500 mt-1">{d.hr?.title || 'Human Resources'}</p>
                </div>
                <button
                    onClick={() => { setFormData({ name: '', position: '', phone: '', base_salary: 0, shift_start: '09:00', shift_end: '17:00', is_active: true, user_id: '' }); setIsModalOpen(true); }}
                    className="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition"
                >
                    {d.hr?.addEmployee || 'Add Employee'}
                </button>
            </div>

            {loading ? (
                <div className="text-center py-10">Loading...</div>
            ) : (
                <div className="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <table className="w-full text-sm text-left">
                        <thead className="text-xs text-slate-500 uppercase bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700" style={{ textAlign: isRTL ? 'right' : 'left' }}>
                            <tr>
                                <th className="px-6 py-4">{d.hr?.employeeName || 'Name'}</th>
                                <th className="px-6 py-4">{d.hr?.position || 'Position'}</th>
                                <th className="px-6 py-4">{d.hr?.baseSalary || 'Salary'}</th>
                                <th className="px-6 py-4">{'Shift'}</th>
                                <th className="px-6 py-4">{d.hr?.status || 'Status'}</th>
                                <th className="px-6 py-4 text-center">{d.hr?.actions || 'Actions'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {employees.map(emp => (
                                <tr key={emp.id} className="border-b border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                    <td className="px-6 py-4 font-medium" style={{ textAlign: isRTL ? 'right' : 'left' }}>{emp.name}</td>
                                    <td className="px-6 py-4" style={{ textAlign: isRTL ? 'right' : 'left' }}>{emp.position || '-'}</td>
                                    <td className="px-6 py-4" style={{ textAlign: isRTL ? 'right' : 'left' }}>{emp.base_salary}</td>
                                    <td className="px-6 py-4" style={{ textAlign: isRTL ? 'right' : 'left' }}>{emp.shift_start} - {emp.shift_end}</td>
                                    <td className="px-6 py-4" style={{ textAlign: isRTL ? 'right' : 'left' }}>
                                        <span className={`px-2 py-1 rounded text-xs ${emp.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                            {emp.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-center">
                                        <button onClick={() => handleEdit(emp)} className="text-blue-600 hover:text-blue-800 mx-2">
                                            {d.common?.edit || 'Edit'}
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {/* Modal */}
            {isModalOpen && (
                <div className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
                    <div className="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl">
                        <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                            <h3 className="text-lg font-bold">{formData.id ? d.hr?.editEmployee : d.hr?.addEmployee}</h3>
                            <button onClick={() => setIsModalOpen(false)} className="text-slate-400 hover:text-slate-600">&times;</button>
                        </div>
                        <form onSubmit={handleSave} className="p-6 space-y-4">
                            <div>
                                <label className="block text-sm font-medium mb-1">{d.hr?.employeeName}</label>
                                <input type="text" value={formData.name} onChange={(e) => setFormData({...formData, name: e.target.value})} className="w-full p-2 border rounded-lg bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700" required />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium mb-1">{d.hr?.position}</label>
                                    <input type="text" value={formData.position} onChange={(e) => setFormData({...formData, position: e.target.value})} className="w-full p-2 border rounded-lg bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">{d.hr?.baseSalary}</label>
                                    <input type="number" step="0.01" value={formData.base_salary} onChange={(e) => setFormData({...formData, base_salary: e.target.value})} className="w-full p-2 border rounded-lg bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700" required />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium mb-1">{d.hr?.shiftStart}</label>
                                    <input type="time" value={formData.shift_start} onChange={(e) => setFormData({...formData, shift_start: e.target.value})} className="w-full p-2 border rounded-lg bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700" />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">{d.hr?.shiftEnd}</label>
                                    <input type="time" value={formData.shift_end} onChange={(e) => setFormData({...formData, shift_end: e.target.value})} className="w-full p-2 border rounded-lg bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700" />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-1">Link to System User (Optional)</label>
                                <select value={formData.user_id || ''} onChange={(e) => setFormData({...formData, user_id: e.target.value})} className="w-full p-2 border rounded-lg bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700">
                                    <option value="">-- None --</option>
                                    {users.map((u: any) => (
                                        <option key={u.id} value={u.id}>{u.name} ({u.email})</option>
                                    ))}
                                </select>
                            </div>
                            
                            <div className="pt-4 flex justify-end gap-3 border-t border-slate-200 dark:border-slate-700 mt-6">
                                <button type="button" onClick={() => setIsModalOpen(false)} className="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200">
                                    {d.common?.cancel}
                                </button>
                                <button type="submit" className="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700">
                                    {d.common?.save}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
