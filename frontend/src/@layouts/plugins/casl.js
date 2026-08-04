import ability from '@/plugins/casl/ability'

const PLAN_GATED_SUBJECTS = new Set([
  'clients',
  'billings',
  'invoices',
  'stock',
  'sold',
  'agreements',
  'signed-documents',
  'payouts',
  'notes',
  'my-team',
  'company',
  'sms'
])

const normalizeSubject = subject => String(subject || '').trim().toLowerCase()

const getStoredUserData = () => {
  try {
    return JSON.parse(localStorage.getItem('user_data') || 'null')
  } catch (error) {
    return null
  }
}

const getPlanFeatureNames = supplier => {
  const directFeatures = Array.isArray(supplier?.plan?.features)
    ? supplier.plan.features
    : []

  const pivotFeaturesRaw = Array.isArray(supplier?.plan?.feature_plans)
    ? supplier.plan.feature_plans
    : Array.isArray(supplier?.plan?.featurePlans)
      ? supplier.plan.featurePlans
      : []

  const pivotFeatures = pivotFeaturesRaw
    .map(item => item?.feature)
    .filter(Boolean)

  return [...directFeatures, ...pivotFeatures]
    .map(feature => normalizeSubject(feature?.name))
    .filter(Boolean)
}

const hasPlanFeatureAccess = subject => {
  const normalizedSubject = normalizeSubject(subject)

  if (!PLAN_GATED_SUBJECTS.has(normalizedSubject))
    return true

  const userData = getStoredUserData()
  if (!userData)
    return false

  const userRole = userData.roles?.[0]?.name
  if (userRole !== 'Supplier' && userRole !== 'User')
    return true

  const supplierSource = userRole === 'User'
    ? userData.supplier?.boss
    : userData.supplier

  if (!supplierSource?.plan_id)
    return false

  const planFeatures = getPlanFeatureNames(supplierSource)

  if (planFeatures.length === 0)
    return false

  return planFeatures.includes(normalizedSubject)
}

const hasAccessByRoleAndPlan = (hasPermission, subject) => {
  if (!hasPermission)
    return false

  if (!hasPlanFeatureAccess(subject))
    return false

  if (subject === 'payouts') {
    const userData = getStoredUserData()
    if (!userData)
      return false

    const userRole = userData.roles?.[0]?.name

    if (userRole === 'Supplier')
      return userData.supplier?.is_payout === 1
  }

  return true
}

export const canWithPlan = (action, subject) => {
  return hasAccessByRoleAndPlan(ability.can(action, subject), subject)
}

/**
 * Returns ability result if ACL is configured or else just return true
 * We should allow passing string | undefined to can because for admin ability we omit defining action & subject
 *
 * Useful if you don't know if ACL is configured or not
 * Used in @core files to handle absence of ACL without errors
 *
 * @param {String} action CASL Actions // https://casl.js.org/v4/en/guide/intro#basics
 * @param {String} subject CASL Subject // https://casl.js.org/v4/en/guide/intro#basics
 * @param {Object} item Optional navigation item for additional checks
 */
export const can = (action, subject, item = null) => {
  const vm = getCurrentInstance()
  if (!vm)
    return false
  const localCan = vm.proxy && '$can' in vm.proxy

  // Verificacion basica de permisos CASL.
  const hasPermission = localCan ? vm.proxy?.$can(action, subject) : true

  return hasAccessByRoleAndPlan(hasPermission, subject)
}

/**
 * Check if user can view item based on it's ability
 * Based on item's action and subject & Hide group if all of it's children are hidden
 * @param {Object} item navigation object item
 */
export const canViewNavMenuGroup = item => {
  const children = Array.isArray(item?.children) ? item.children : []
  const hasAnyVisibleChild = children.some(i => can(i.action, i.subject))

  // If subject and action is defined in item => Return based on children visibility (Hide group if no child is visible)
  // Else check for ability using provided subject and action along with checking if has any visible child
  if (!(item?.action && item?.subject))
    return hasAnyVisibleChild

  return can(item.action, item.subject) && hasAnyVisibleChild
}
export const canNavigate = to => {
  return to.matched.some(route => {
    const permissionsAny = Array.isArray(route.meta?.permissionsAny)
      ? route.meta.permissionsAny
      : []

    if (permissionsAny.length > 0)
      return permissionsAny.some(permission => canWithPlan(permission.action, permission.subject))

    return canWithPlan(route.meta.action, route.meta.subject)
  })
}
