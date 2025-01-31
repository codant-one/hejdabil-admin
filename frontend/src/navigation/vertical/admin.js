export default [
  { 
    title: 'ADMINISTRATION',
    icon: { icon:'tabler-home-cog' },
    children:[
      { 
        title: 'Attributes', 
        icon: { icon: 'mdi-database-cog' },
        action: 'view',
        subject: 'invoices',
        children: [
          { 
            title: 'Invoices', 
            to: 'dashboard-admin-invoices', 
            action: 'view', 
            subject: 'invoices', 
          }
        ]
      }
    ]
  }
]
