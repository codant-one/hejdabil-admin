export default [
    { 
        title: 'Mina fordon',
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
                title: 'Lagerfordon', 
                to: 'dashboard-admin-stock', 
                action: 'view', 
                subject: 'stock', 
            },
                { 
                title: 'Sålda', 
                to: 'dashboard-admin-sold', 
                action: 'view', 
                subject: 'sold', 
            },
                { 
                title: 'Egen värdering', 
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
