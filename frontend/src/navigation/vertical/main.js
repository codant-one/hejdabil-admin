export default [
    // { 
    //   heading: 'MODULER'
    // },
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
      subject: 'billing'
    },
    { 
      title: 'Leverantörer', 
      icon: { icon: 'mdi-account-tie' },
      to: 'dashboard-admin-suppliers', 
      action: 'view' , 
      subject: 'suppliers'
    },
  ]
  