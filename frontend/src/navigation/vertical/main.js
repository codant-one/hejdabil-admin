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
      title: 'Användare', 
      icon: { icon: 'mdi-account-star' },
      to: 'dashboard-supplier-users', 
      action: 'view', 
      subject: 'users'
    },
    { 
      title: 'Fakturor', 
      icon: { icon: 'tabler-clipboard-list' },
      to: 'dashboard-admin-billings', 
      action: 'view', 
      subject: 'billing'
    }
  ]
  