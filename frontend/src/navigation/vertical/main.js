export default [
    { 
      heading: 'MODULER'
    },
    { 
      title: 'Leverantörer', 
      icon: { icon: 'mdi-account-tie' },
      to: 'dashboard-admin-suppliers', 
      action: 'view' , 
      subject: 'suppliers'
    },
    { 
      title: 'Kunder', 
      icon: { icon: 'mdi-account-star' },
      to: 'dashboard-admin-clients', 
      action: 'view', 
      subject: 'clients'
    },
    { 
      title: 'Fakturor', 
      icon: { icon: 'tabler-clipboard-list' },
      to: 'dashboard-admin-billings', 
      action: 'view', 
      subject: 'billings'
    },
    { 
      title: 'Signera dokument', 
      icon: { icon: 'mdi-draw' },
      to: 'dashboard-admin-documents', 
      action: 'view', 
      subject: 'signed-documents'
    },
    { 
      title: 'Betalningar', 
      icon: { icon: 'mdi-payment' },
      to: 'dashboard-admin-payouts', 
      action: 'view', 
      subject: 'payouts'
    }
  ]
  