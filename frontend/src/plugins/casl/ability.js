import { Ability } from '@casl/ability'

export const initialAbility = [
  {
    action: 'view',
    subject: 'Auth',
  },
]

//  Read ability from localStorage
// 👉 Handles auto fetching previous abilities if already logged in user
// ℹ️ You can update this if you store user abilities to more secure place
// ❗ Anyone can update localStorage so be careful and please update this
const stringifiedUserAbilities = localStorage.getItem('userAbilities')
const existingAbility = stringifiedUserAbilities ? JSON.parse(stringifiedUserAbilities) : null

const WRITE_ACTIONS = new Set(['create', 'edit', 'delete'])

const getStoredUserData = () => {
  try {
    return JSON.parse(localStorage.getItem('user_data') || 'null')
  } catch (error) {
    return null
  }
}

const isInactiveSupplierSubscription = () => {
  const userData = getStoredUserData()
  if (!userData)
    return false

  const userRole = userData.roles?.[0]?.name
  if (userRole !== 'Supplier' && userRole !== 'User')
    return false

  const supplierSource = userRole === 'User'
    ? userData.supplier?.boss
    : userData.supplier

  return Number(supplierSource?.is_subscription_active) === 0
}

const ability = new Ability(existingAbility || initialAbility)
const nativeCan = ability.can.bind(ability)

ability.can = (action, subject, field) => {
  const hasPermission = nativeCan(action, subject, field)
  if (!hasPermission)
    return false

  const normalizedAction = String(action || '').toLowerCase()

  if (WRITE_ACTIONS.has(normalizedAction) && isInactiveSupplierSubscription())
    return false

  return true
}

export default ability
