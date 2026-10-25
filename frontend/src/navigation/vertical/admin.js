export default [
  {
    title: 'Administration',
    icon: { icon:'custom-cog' },
    children:[
      { 
        title: 'Attribut', 
        icon: { icon: 'custom-attribut' },
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
        icon: { icon: 'custom-pris-information' },
        to: 'dashboard-admin-currencies', 
        action: 'view',
        subject: 'invoices'
      },
      { 
        title: 'Land', 
        icon: { icon: 'custom-flag' },
        to: 'dashboard-admin-countries', 
        action: 'view',
        subject: 'countries'
      },
      { 
        title: 'Planer', 
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
