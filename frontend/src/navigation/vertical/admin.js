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
        title: 'Hjälpcenter', 
        icon: { icon: 'custom-help' },
        action: 'view',
        subject: 'help-categories',
        children: [
          { 
            title: 'Kategorier', 
            to: 'dashboard-admin-help-categories', 
            action: 'view', 
            subject: 'help-categories', 
          },
          { 
            title: 'Videor', 
            to: 'dashboard-admin-help-videos', 
            action: 'view', 
            subject: 'help-videos', 
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
    to: 'dashboard-admin-help-center',
    action: 'view' , 
    subject: 'help-center'
  }
]
