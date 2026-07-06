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
      },
      { 
        title: 'Land', 
        icon: { icon: 'mdi-flag' },
        to: 'dashboard-admin-countries', 
        action: 'view',
        subject: 'countries'
      },
      { 
        title: 'Pllanera', 
        icon: { icon: 'custom-cash' },
        to: 'dashboard-admin-plans', 
        action: 'view',
        subject: 'plans'
      }
    ]
  },
  {
    title: 'Hjälp',
    icon: { icon:'custom-help' },
    class: 'help-button',
    action: 'view' , 
    subject: 'dashboard'
  }
]
