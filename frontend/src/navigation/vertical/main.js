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
    title: 'Mitt Fordonslager',
    icon: { icon:'custom-car' },
    children:[
      { 
          title: 'Märke', 
          to: 'dashboard-admin-brands', 
          action: 'view', 
          subject: 'brands',
          icon: { icon:'custom-point' },
      },
      { 
          title: 'Modell', 
          to: 'dashboard-admin-models', 
          action: 'view', 
          subject: 'models', 
      },
      { 
          title: 'I Lager', 
          to: 'dashboard-admin-stock', 
          action: 'view', 
          subject: 'stock',
          icon: {icon:'custom-lager'}, 
      },
          { 
          title: 'Sålda Fordon', 
          to: 'dashboard-admin-sold', 
          action: 'view', 
          subject: 'sold', 
          icon: {icon: 'custom-sold'},
      }
    ]
  },
  { 
    title: 'Avtal', 
    icon: { icon: 'custom-contract' },
    to: 'dashboard-admin-agreements', 
    action: 'view', 
    subject: 'agreements'
  },
  { 
    title: 'Signera dokument', 
    icon: { icon: 'custom-signature' },
    to: 'dashboard-admin-documents', 
    action: 'view', 
    subject: 'signed-documents'
  },
  { 
    title: 'Swish', 
    icon: { icon: 'custom-cash-2' },
    to: 'dashboard-admin-payouts', 
    action: 'view', 
    subject: 'payouts'
  },
  { 
    title: 'Mina Värderingar', 
    icon: {icon: 'custom-cash'}, 
    to: 'dashboard-admin-notes',  
    action: 'view', 
    subject: 'notes'
  }
]
  