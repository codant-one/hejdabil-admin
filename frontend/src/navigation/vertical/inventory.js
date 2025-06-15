export default [
    { 
        title: 'Mina fordon',
        icon: { icon:'mdi-car' },
        children:[
            { 
                title: 'Märke', 
                to: 'dashboard-admin-brands', 
                action: 'view', 
                subject: 'invoices', 
            },
            { 
                title: 'Modell', 
                to: 'dashboard-admin-models', 
                action: 'view', 
                subject: 'invoices', 
            },
            { 
                title: 'Lagerfordon', 
                to: 'dashboard-admin-stock', 
                action: 'view', 
                subject: 'invoices', 
            },
                { 
                title: 'Sålda', 
                to: 'dashboard-admin-sold', 
                action: 'view', 
                subject: 'invoices', 
            },
                { 
                title: 'Egen värdering', 
                to: 'dashboard-admin-notes', 
                action: 'view', 
                subject: 'invoices', 
            }
        ]
    },
    { 
      title: 'Avtal', 
      icon: { icon: 'mdi-file-sign' },
      to: 'dashboard-admin-sales', 
      action: 'view', 
      subject: 'billing'
    }
]
