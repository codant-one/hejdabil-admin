# Checklist: Publicar una actualización de Bilflogg (Android)

Sigue este proceso cada vez que haya cambios en el código (frontend Vue o
configuración nativa) que deban reflejarse en la app publicada/en pruebas.

---

## 1. Preparar el código

- [ ] Haz merge/pull de los cambios a la rama que usas para el build mobile.
- [ ] Si cambiaste dependencias, corre `npm install`.
- [ ] Verifica que `.env.production` siga apuntando a las URLs correctas
      (API, Pusher, etc.) — especialmente si vas a pasar de staging a producción.

## 2. Build del frontend y sync con Capacitor

```bash
npm run build
npx cap sync
```

- [ ] Confirma que no haya errores en el `build` ni en el `sync`.
- [ ] Si agregaste un plugin nuevo de Capacitor, revisa que no aparezca el
      error de `compileSdk`/AGP que ya resolvimos antes (variables.gradle a 36,
      AGP a 8.9.1+).

## 3. Incrementar la versión (OBLIGATORIO en cada release)

Abre `android/app/build.gradle` y busca el bloque `defaultConfig`:

```gradle
defaultConfig {
    ...
    versionCode 2        // ⬅️ sube este número en +1 cada vez, sin excepción
    versionName "1.0.1"  // ⬅️ sube esto también (semántico, para humanos)
}
```

- [ ] `versionCode` incrementado (Play Console **rechaza** un `.aab` con el
      mismo `versionCode` que uno ya subido, sin importar el contenido).
- [ ] `versionName` actualizado (opcional pero recomendado, ej. `1.0.1`, `1.0.2`).

## 4. Generar el nuevo .aab firmado

En Android Studio:

1. `Build > Generate Signed Bundle / APK`
2. Selecciona **Android App Bundle**
3. En "Key store path", usa el **mismo Keystore** que ya tienes guardado
   (nunca crear uno nuevo — usar uno distinto invalida la firma de la app
   y Play Console lo rechazará).
4. Ingresa las mismas contraseñas guardadas.
5. Build Variant: `release`
6. Finish, espera a que compile.

- [ ] `.aab` generado en `android/app/release/app-release.aab`.

## 5. Subir a Play Console

1. Ve a tu app > **Testing > Internal testing** (o el track que estés usando)
2. **Create new release**
3. Sube el nuevo `.aab`
4. Agrega notas de la versión (qué cambió, en 1-2 líneas)
5. **Review release > Start rollout**

- [ ] Release publicado en el track correspondiente.
- [ ] Testers notificados (Play Store actualiza la app automáticamente en sus
      dispositivos si tienen actualizaciones automáticas activadas; si no,
      deben actualizar manualmente desde la Play Store).

---

## Notas rápidas

- **Cambios solo de frontend (Vue)** → pasos 1-2 son suficientes en cuanto a
  contenido, pero igual necesitas pasos 3-5 para que llegue a los usuarios
  (el `.aab` empaqueta el `dist/` actualizado).
- **Cambios de plugins nativos o `capacitor.config.ts`** → revisa siempre
  que el sync (paso 2) no arroje conflictos de Gradle antes de generar el
  build firmado.
- **El Keystore nunca cambia entre releases.** Si lo pierdes, no hay forma
  de publicar actualizaciones a esta misma app — tendrías que crear una app
  nueva desde cero en Play Store.
- **Nunca subas un `.aab` sin haber incrementado `versionCode`** — es el
  error más común y Play Console lo rechaza de inmediato con un mensaje claro.