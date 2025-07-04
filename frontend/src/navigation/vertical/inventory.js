export default [
    { 
        title: 'Mitt Fordonslager',
        icon: { icon:'mdi-car' },
        children:[
            { 
                title: 'Märke', 
                to: 'dashboard-admin-brands', 
                action: 'view', 
                subject: 'brands', 
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
            },
                { 
                title: 'Sålda Fordon', 
                to: 'dashboard-admin-sold', 
                action: 'view', 
                subject: 'sold', 
            },
                { 
                title: 'Mina Värderingar', 
                to: 'dashboard-admin-notes', 
                action: 'view', 
                subject: 'notes', 
            }
        ]
    },
    { 
      title: 'Avtal', 
      icon: { icon: 'mdi-file-sign' },
      to: 'dashboard-admin-agreements', 
      action: 'view', 
      subject: 'agreements'
    }
]
