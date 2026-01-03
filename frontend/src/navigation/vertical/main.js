export default [
  { 
    title: 'Kunder', 
    icon: { icon: 'custom-clients' },
    to: 'dashboard-admin-clients', 
    action: 'view', 
    subject: 'clients'
  },
  { 
    title: 'Fakturor', 
    icon: { icon: 'custom-facture' },
    to: 'dashboard-admin-billings', 
    action: 'view', 
    subject: 'billings'
  },
  { 
    title: 'Leverantörer', 
    icon: { icon: 'mdi-account-tie' },
    to: 'dashboard-admin-suppliers', 
    action: 'view' , 
    subject: 'suppliers'
  },
  { 
    title: 'Signera dokument', 
    icon: { icon: 'custom-signature' },
    to: 'dashboard-admin-documents', 
    action: 'view', 
    subject: 'signed-documents'
  },
  { 
    title: 'Betalningar', 
    icon: { icon: 'custom-cash-2' },
    to: 'dashboard-admin-payouts', 
    action: 'view', 
    subject: 'payouts'
  }
]
  