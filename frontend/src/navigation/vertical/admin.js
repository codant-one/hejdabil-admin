export default [
  { 
    title: 'ADMINISTRATION',
    icon: { icon:'tabler-home-cog' },
    children:[
      { 
        title: 'Attribut', 
        icon: { icon: 'mdi-database-cog' },
        action: 'view',
        subject: 'invoices',
        children: [
          { 
            title: 'Fakturor', 
            to: 'dashboard-admin-invoices', 
            action: 'view', 
            subject: 'invoices', 
          }
        ]
      }
    ]
  }
]
