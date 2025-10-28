export default [
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
            },
                { 
                title: 'Mina Värderingar', 
                to: 'dashboard-admin-notes', 
                action: 'view', 
                subject: 'notes',
                icon: {icon: 'custom-cash'}, 
            }
        ]
    },
    { 
      title: 'Avtal', 
      icon: { icon: 'custom-contract' },
      to: 'dashboard-admin-agreements', 
      action: 'view', 
      subject: 'agreements'
    }
]
