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
      },
      { 
        title: 'Valuta', 
        icon: { icon: 'mdi-currency-usd' },
        to: 'dashboard-admin-currencies', 
        action: 'view',
        subject: 'invoices'
      }
    ]
  },
  {
    title: 'Hjälp',
    icon: { icon:'custom-help' },
    class: 'help-button'
  }
]
