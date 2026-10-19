# vue

## Native biometric login

On Android/iOS, password login offers biometric enrollment after authentication
(including completion of enabled 2FA). The login button is shown only when
biometrics are available and enrollment is enabled for the current account.
Both profile security screens provide a disable switch.

The device token from `POST /auth/device-token` is stored under `bilflogg.se`
using Native Biometric credentials with `BIOMETRY_CURRENT_SET`. The plugin's
`password` field contains only this token, never the account password.
`getSecureCredentials()` performs biometric verification while reading the
protected token. Availability requires strong biometrics, as required by the
plugin's hardware-protected credential flow (weak-only Android face unlock is
not offered). Preferences stores only the enrolled user ID as the enabled
flag; the device token is never stored in Preferences or localStorage.

Device login uses the same session/ability/navigation handler as password login,
including `2fa` and `2fa-generate`. A `login_success` response does not leave
pending 2FA even if the account has 2FA enabled. HTTP 401 clears local enrollment
and asks the user to log in with a password. Cancellation and connection errors
do not delete enrollment. Changed biometric enrollment may invalidate protected
credentials and require password login again.

Disabling revokes **all** account device tokens through the backend before
deleting local credentials and the flag. Network failure leaves enrollment
enabled so revocation can be retried. Password changes on the backend also
revoke these tokens; a subsequent biometric login clears them on HTTP 401.

The web platform never invokes the biometric plugin, Preferences or device
authentication endpoints. Password login remembers only the email, not the
password, and removes the legacy saved password.

Android's biometric permission is already declared. iOS requires the included
`NSFaceIDUsageDescription`. After building the web app, sync native assets with
`npx cap sync` and rebuild the native projects. Biometric prompts and secure
storage must also be tested on enrolled Android/iOS devices.

Run the isolated composable regression tests without additional dependencies:

```sh
node --experimental-vm-modules --test tests/biometric-login.test.cjs
```

This template should help get you started developing with Vue 3 in Vite.

## Recommended IDE Setup

[VSCode](https://code.visualstudio.com/) + [Volar](https://marketplace.visualstudio.com/items?itemName=johnsoncodehk.volar) (and disable Vetur).

## Type Support for `.vue` Imports in TS

Since TypeScript cannot handle type information for `.vue` imports, they are shimmed to be a generic Vue component type by default. In most cases this is fine if you don't really care about component prop types outside of templates.

However, if you wish to get actual prop types in `.vue` imports (for example to get props validation when using manual `h(...)` calls), you can run `Volar: Switch TS Plugin on/off` from VSCode command palette.

## Customize configuration

See [Vite Configuration Reference](https://vitejs.dev/config/).

## Project Setup

```sh
npm install
```

### Compile and Hot-Reload for Development

```sh
npm run dev
```

### Type-Check, Compile and Minify for Production

```sh
npm run build
```
