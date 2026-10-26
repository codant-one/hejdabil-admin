// Build "essential" de vue-pdf-embed: usa el pdfjs-dist instalado en el proyecto
// (build legacy, ya importado por index.essential.mjs) con su worker legacy servido
// como archivo propio. Importar siempre VuePdfEmbed desde aquí para que el worker
// quede configurado.
//
// No se importa además 'pdfjs-dist/legacy/build/pdf.min.mjs': index.essential.mjs ya
// importa 'pdfjs-dist/legacy/build/pdf.mjs' y una segunda copia tendría su propio
// GlobalWorkerOptions (Vite minifica igualmente en el build).

import VuePdfEmbed, { GlobalWorkerOptions } from 'vue-pdf-embed/dist/index.essential.mjs'
import pdfWorkerUrl from 'pdfjs-dist/legacy/build/pdf.worker.min.mjs?url'

GlobalWorkerOptions.workerSrc = pdfWorkerUrl

export default VuePdfEmbed
