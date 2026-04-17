import axios from 'axios';

const api = axios.create({
    baseURL: process.env.NEXT_PUBLIC_API_URL || '/api',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
    timeout: 30000,
});

// Request interceptor - attach token and tenant ID
api.interceptors.request.use((config) => {
    if (typeof window !== 'undefined') {
        const token = localStorage.getItem('auth_token');
        const tenantId = localStorage.getItem('tenant_id');

        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        if (tenantId) {
            config.headers['X-Tenant-ID'] = tenantId;
        }
    }
    return config;
});

// Response interceptor - handle errors
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            if (typeof window !== 'undefined') {
                localStorage.removeItem('auth_token');
                window.location.href = '/en/login';
            }
        }
        return Promise.reject(error);
    }
);

export default api;

// API helper functions
export const authApi = {
    login: (data: { email: string; password: string }) =>
        api.post('/auth/login', data),
    register: (data: {
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
    }) => api.post('/auth/register', data),
    me: () => api.get('/auth/me'),
    logout: () => api.post('/auth/logout'),
};

export const salesApi = {
    // Invoices
    getInvoices: (params?: Record<string, any>) => api.get('/sales/invoices', { params }),
    getInvoice: (id: string) => api.get(`/sales/invoices/${id}`),
    createInvoice: (data: any) => api.post('/sales/invoices', data),
    updateInvoice: (id: string, data: any) => api.put(`/sales/invoices/${id}`, data),
    updateInvoiceStatus: (id: string, status: string) => api.put(`/sales/invoices/${id}/status`, { status }),
    syncBulkInvoices: (invoices: any[]) => api.post('/sales/invoices/bulk', { invoices }),
    getSalesReport: (params?: { from?: string; to?: string }) => api.get('/sales/reports/sales', { params }),

    // Returns
    getReturns: (params?: Record<string, any>) => api.get('/sales/returns', { params }),
    getReturn: (id: string) => api.get(`/sales/returns/${id}`),
    createReturn: (data: any) => api.post('/sales/returns', data),
    updateReturnStatus: (id: string, status: string) => api.put(`/sales/returns/${id}/status`, { status }),

    // Quotations
    getQuotations: (params?: Record<string, any>) => api.get('/sales/quotations', { params }),
    getQuotation: (id: string) => api.get(`/sales/quotations/${id}`),
    createQuotation: (data: any) => api.post('/sales/quotations', data),
    updateQuotation: (id: string, data: any) => api.put(`/sales/quotations/${id}`, data),
    updateQuotationStatus: (id: string, status: string) => api.put(`/sales/quotations/${id}/status`, { status }),

    // Shipping
    getShippingInvoices: (params?: Record<string, any>) => api.get('/sales/shipping', { params }),
    getShippingInvoice: (id: string) => api.get(`/sales/shipping/${id}`),
    createShippingInvoice: (data: any) => api.post('/sales/shipping', data),
    updateShippingInvoice: (id: string, data: any) => api.put(`/sales/shipping/${id}`, data),
    updateShippingStatus: (id: string, status: string) => api.put(`/sales/shipping/${id}/status`, { status }),
};

export const treasuryApi = {
    // Safes
    getSafes: () => api.get('/treasury/safes'),
    createSafe: (data: any) => api.post('/treasury/safes', data),
    assignUser: (id: string, data: any) => api.post(`/treasury/safes/${id}/assign-user`, data),
    
    // Transactions
    createTransaction: (data: any) => api.post('/treasury/transactions', data),
    transfer: (data: any) => api.post('/treasury/transfer', data),

    // Expenses
    getExpenseCategories: () => api.get('/expenses/categories'),
    createExpenseCategory: (data: any) => api.post('/expenses/categories', data),
    getExpenses: (params?: Record<string, any>) => api.get('/expenses', { params }),
    createExpense: (data: any) => api.post('/expenses', data),
};

export const hrApi = {
    // Employees
    getEmployees: () => api.get('/hr/employees'),
    createEmployee: (data: any) => api.post('/hr/employees', data),

    // Attendance
    getAttendances: (params?: Record<string, any>) => api.get('/hr/attendance', { params }),
    checkIn: (data: any) => api.post('/hr/attendance/check-in', data),
    checkOut: (data: any) => api.post('/hr/attendance/check-out', data),

    // Leaves
    getLeaves: () => api.get('/hr/leaves'),
    applyLeave: (data: any) => api.post('/hr/leaves', data),

    // Payrolls
    getPayrolls: () => api.get('/hr/payrolls'),
    generatePayroll: (data: any) => api.post('/hr/payrolls/generate', data),
    payPayroll: (id: string, data: any) => api.post(`/hr/payrolls/${id}/pay`, data),
};

export const inventoryApi = {
    // Branches
    getBranches: (params?: Record<string, any>) => api.get('/inventory/branches', { params }),
    getBranch: (id: string) => api.get(`/inventory/branches/${id}`),
    createBranch: (data: any) => api.post('/inventory/branches', data),
    updateBranch: (id: string, data: any) => api.put(`/inventory/branches/${id}`, data),
    deleteBranch: (id: string) => api.delete(`/inventory/branches/${id}`),

    // Warehouses
    getWarehouses: (params?: Record<string, any>) => api.get('/inventory/warehouses', { params }),

    // Stock Transfers
    getStockTransfers: (params?: Record<string, any>) => api.get('/inventory/stock-transfers', { params }),
    getStockTransfer: (id: string) => api.get(`/inventory/stock-transfers/${id}`),
    createStockTransfer: (data: any) => api.post('/inventory/stock-transfers', data),
    approveStockTransfer: (id: string) => api.post(`/inventory/stock-transfers/${id}/approve`),
    receiveStockTransfer: (id: string, data?: any) => api.post(`/inventory/stock-transfers/${id}/receive`, data),
    deleteStockTransfer: (id: string) => api.delete(`/inventory/stock-transfers/${id}`),

    // Products
    getProducts: (params?: Record<string, any>) =>
        api.get('/inventory/products', { params }),
    createProduct: (data: any) => api.post('/inventory/products', data),
    getProduct: (id: string) => api.get(`/inventory/products/${id}`),
    updateProduct: (id: string, data: any) => api.put(`/inventory/products/${id}`, data),
    deleteProduct: (id: string) => api.delete(`/inventory/products/${id}`),
    searchProducts: (q: string) =>
        api.get('/inventory/products/search', { params: { q } }),
    scanBarcode: (barcode: string) =>
        api.get(`/inventory/products/barcode/${barcode}`),
    getLowStock: (warehouseId?: string) =>
        api.get('/inventory/products/low-stock', {
            params: { warehouse_id: warehouseId },
        }),

    // Adjustments
    getAdjustments: (params?: Record<string, any>) => api.get('/inventory/adjustments', { params }),
    createAdjustment: (data: any) => api.post('/inventory/adjustments', data),
    getAdjustment: (id: string) => api.get(`/inventory/adjustments/${id}`),

    // Assembly (BOM)
    getBOM: (productId: string) => api.get(`/inventory/assembly/${productId}`),
    setBOM: (productId: string, data: any) => api.post(`/inventory/assembly/${productId}`, data),
    assemble: (data: any) => api.post('/inventory/assemble', data),
};

export const crmApi = {
    getCustomers: (params?: Record<string, any>) => api.get('/crm/customers', { params }),
    getCustomer: (id: string) => api.get(`/crm/customers/${id}`),
    createCustomer: (data: any) => api.post('/crm/customers', data),
    updateCustomer: (id: string, data: any) => api.put(`/crm/customers/${id}`, data),
    deleteCustomer: (id: string) => api.delete(`/crm/customers/${id}`),
    exportCustomers: () => api.get('/crm/customers/export', { responseType: 'blob' }).then(res => res.data),
    importCustomers: (formData: FormData) => api.post('/crm/customers/import', formData, { headers: { 'Content-Type': 'multipart/form-data' } }).then(res => res.data),
    getCustomerStatement: (id: string) => api.get(`/crm/customers/${id}/statement`).then(res => res.data),
    
    // Vouchers
    createVoucher: (data: any) => api.post('/crm/vouchers', data).then(res => res.data),

    getSuppliers: (params?: Record<string, any>) => api.get('/crm/suppliers', { params }),
    getSupplier: (id: string) => api.get(`/crm/suppliers/${id}`),
    createSupplier: (data: any) => api.post('/crm/suppliers', data),
    updateSupplier: (id: string, data: any) => api.put(`/crm/suppliers/${id}`, data),
    deleteSupplier: (id: string) => api.delete(`/crm/suppliers/${id}`),
    exportSuppliers: () => api.get('/crm/suppliers/export', { responseType: 'blob' }).then(res => res.data),
    importSuppliers: (formData: FormData) => api.post('/crm/suppliers/import', formData, { headers: { 'Content-Type': 'multipart/form-data' } }).then(res => res.data),
    getSupplierStatement: (id: string) => api.get(`/crm/suppliers/${id}/statement`).then(res => res.data),
};

export const purchasesApi = {
    getInvoices: (params?: Record<string, any>) => api.get('/purchases/invoices', { params }),
    getInvoice: (id: string) => api.get(`/purchases/invoices/${id}`),
    createInvoice: (data: any) => api.post('/purchases/invoices', data),
    updateInvoice: (id: string, data: any) => api.put(`/purchases/invoices/${id}`, data),
    updateStatus: (id: string, payload: any) => api.put(`/purchases/invoices/${id}/status`, payload),
};

export const purchaseReturnsApi = {
    getReturns: (params?: Record<string, any>) => api.get('/purchases/returns', { params }),
    getReturn: (id: string) => api.get(`/purchases/returns/${id}`),
    createReturn: (data: any) => api.post('/purchases/returns', data),
    updateStatus: (id: string, payload: any) => api.put(`/purchases/returns/${id}/status`, payload),
};

export const usersApi = {
    getUsers: (params?: Record<string, any>) => api.get('/users', { params }),
    getUser: (id: string) => api.get(`/users/${id}`),
    createUser: (data: any) => api.post('/users', data),
    updateUser: (id: string, data: any) => api.put(`/users/${id}`, data),
    deleteUser: (id: string) => api.delete(`/users/${id}`),
};

export const partnershipsApi = {
    getPartners: (params?: Record<string, any>) => api.get('/partnerships/partners', { params }),
    getPartner: (id: string) => api.get(`/partnerships/partners/${id}`),
    createPartner: (data: any) => api.post('/partnerships/partners', data),
    updatePartner: (id: string, data: any) => api.put(`/partnerships/partners/${id}`, data),
    deletePartner: (id: string) => api.delete(`/partnerships/partners/${id}`),
    withdrawProfits: (id: string, amount: number) => api.post(`/partnerships/partners/${id}/withdraw`, { amount }),
    
    getDistributions: (params?: Record<string, any>) => api.get('/partnerships/distributions', { params }),
    previewDistribution: (params: { period_start: string; period_end: string }) => api.get('/partnerships/distributions/preview', { params }),
    distributeProfits: (data: { period_start: string; period_end: string; notes?: string }) => api.post('/partnerships/distributions', data),
};

export const accountingApi = {
    getChartOfAccounts: () => api.get('/accounting/chart-of-accounts'),
    getJournalEntries: (params?: Record<string, any>) =>
        api.get('/accounting/journal-entries', { params }),
    getTrialBalance: (asOf?: string) =>
        api.get('/accounting/reports/trial-balance', { params: { as_of: asOf } }),
    getIncomeStatement: (params?: { from?: string; to?: string }) =>
        api.get('/accounting/reports/income-statement', { params }),
    getBalanceSheet: (asOf?: string) =>
        api.get('/accounting/reports/balance-sheet', { params: { as_of: asOf } }),
    getGeneralLedger: (params?: { from?: string; to?: string }) =>
        api.get('/accounting/reports/general-ledger', { params }),
};


export const analyticsApi = {
    getInventoryForecast: (threshold?: number) => 
        api.get('/analytics/inventory-forecast', { params: { threshold } }),
    autoDraftPurchaseOrder: (warehouseId: string) => 
        api.post('/analytics/auto-draft-po', { warehouse_id: warehouseId }),
    getPartnerForecast: () => 
        api.get('/analytics/partner-forecast'),
};

export const reportsApi = {
    getProfitAndLoss: (params?: any) => api.get('/reports/pl', { params }),
    getInventoryReport: () => api.get('/reports/inventory'),
    getAccountsReport: () => api.get('/reports/accounts'),
    getGeneralKpis: () => api.get('/reports/kpis'),
    getVatReport: (params?: { year?: string; period?: 'monthly' | 'quarterly'; value?: string }) => 
        api.get('/reports/vat-report', { params }),
};

export const settingsApi = {
    getSettings: () => api.get('/settings'),
    updateSettings: (data: any) => api.put('/settings', data),
};
