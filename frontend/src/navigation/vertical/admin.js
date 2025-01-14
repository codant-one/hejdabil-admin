export default [
  { 
    title: 'ADMINISTRATION',
    icon: { icon:'tabler-home-cog' },
    children:[
      { 
        title: 'Attributes', 
        icon: { icon: 'mdi-database-cog' },
        // action: 'ver',
        // subject: 'atributos',
        children: [
          { 
            title: 'Invoices', 
            to: 'dashboard-admin-invoices', 
            // action: 'ver', 
            // subject: 'atributos', 
          }
        ]
      }
    ]
  }
]
