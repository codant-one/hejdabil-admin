<script setup>
import { useDisplay } from "vuetify";
import {
  emailValidator,
  requiredValidator,
  phoneValidator,
  urlValidator,
  minLengthDigitsValidator,
} from "@/@core/utils/validators";
import { useAuthStores } from "@/stores/useAuth";
import { useProfileStores } from "@/stores/useProfile";
import { Cropper } from "vue-advanced-cropper";
import { themeConfig } from "@themeConfig";

import background from "@images/pages/complete-profile/complete-profile-background.jpg";
import logo_gradient from "@images/logo.svg";

import "vue-advanced-cropper/dist/style.css";
import SignaturePad from "signature_pad";
import { nextTick } from "vue";

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const authStores = useAuthStores();
const profileStores = useProfileStores();

const refVForm = ref();
const user_id = ref("");
const email = ref("");
const name = ref("");
const last_name = ref("");
const phone = ref("");
const address = ref("");
const avatar = ref("");

const avatarOld = ref("");
const isRequestOngoing = ref(false);

const currentTab = ref(0);
const controlledTab = computed({
  get: () => currentTab.value,
  set: async (nextTab) => {
    // Always allow going back to Tab 0 without validation and clear any alert
    if (nextTab === 0) {
      currentTab.value = 0;
      if (alert.value.show) {
        alert.value.show = false;
        alert.value.message = "";
      }
      return;
    }
    // Always allow navigating back or staying on the same tab
    if (nextTab <= currentTab.value) {
      currentTab.value = nextTab;
      return;
    }

    // If attempting to go to Tab 1, validate only Tab 0 fields first
    if (nextTab === 1) {
      const isTab0Valid = await validateTab0();
      if (isTab0Valid) {
        currentTab.value = nextTab;
      } else {
        alert.value.type = "error";
        alert.value.message = "Fyll i alla obligatoriska uppgifter innan du fortsätter.";
        alert.value.show = true;
        await refVForm.value?.validate();
        // Auto-hide after a short delay
        setTimeout(() => {
          alert.value.show = false;
          alert.value.message = "";
        }, 1000);
      }
      return;
    }

    // Default: allow
    currentTab.value = nextTab;
  },
});

// Validate only the fields belonging to Tab 0
const validateTab0 = async () => {
  const results = [];

  results.push(requiredValidator(name.value));
  results.push(requiredValidator(last_name.value));
  results.push(requiredValidator(email.value));
  results.push(emailValidator(email.value));
  results.push(requiredValidator(phone.value));
  results.push(phoneValidator(phone.value));
  results.push(requiredValidator(address.value));

  // All validators from Vuetify return true or a string message
  const isValid = results.every((r) => r === true);
  return isValid;
};

// Validate only the fields belonging to Tab 1 (respecting disabled fields by role)
const validateTab1 = async () => {
  const results = [];

  const isUser = role.value === "User";
  const orgDisabled = role.value === "Supplier" || role.value === "User";

  if (!isUser) results.push(requiredValidator(form.value.company));

  if (!orgDisabled) {
    results.push(requiredValidator(form.value.organization_number));
    results.push(minLengthDigitsValidator(10)(form.value.organization_number));
  }

  if (!isUser) results.push(requiredValidator(form.value.address));

  if (!isUser) results.push(requiredValidator(form.value.postal_code));

  if (!isUser) results.push(requiredValidator(form.value.street));

  if (!isUser) {
    results.push(requiredValidator(form.value.phone));
    results.push(phoneValidator(form.value.phone));
  }

  if (form.value.link) results.push(urlValidator(form.value.link));

  if (!isUser) results.push(requiredValidator(form.value.bank));

  if (!isUser) results.push(requiredValidator(form.value.account_number));

  if (form.value.swish) results.push(phoneValidator(form.value.swish));

  results.push(requiredValidator(acceptPrivacy.value));

  const isValid = results.every((r) => r === true);
  return isValid;
};

const data = ref(null);
const userData = ref(null);
const role = ref(null);

const isConfirmChangeLogoVisible = ref(false);
const cropper = ref();

const logo = ref(null);
const logoCropped = ref(null);
const logoOld = ref(null);
const filename = ref([]);

const isConfirmChangeSignatureVisible = ref(false); // Para el diálogo de la firma
const cropperSignature = ref(); // Referencia para el nuevo cropper de la firma
const signature = ref(null); // URL de la firma actual
const signatureCropped = ref(null); // Imagen de la firma para el cropper
const signatureOld = ref(null); // Blob de la firma recortada, lista para enviar
const signatureFilename = ref([]); // Para el v-model del nuevo VFileInput

const isSignaturePadDialogVisible = ref(false);
const signaturePadCanvas = ref(null);
const signaturePadInstance = ref(null);

const form = ref({
  company: "",
  organization_number: "",
  address: "",
  street: "",
  postal_code: "",
  phone: "",
  link: "",
  bank: "",
  iban: "",
  account_number: "",
  iban_number: "",
  bic: "",
  plus_spin: "",
  swish: "",
  vat: "",
  payout_number: "",
});

// Nuevo: checkbox required
const acceptPrivacy = ref(false);

const tabs = [
  {
    icon: "custom-profile",
    title: "Grundläggande information",
  },
  {
    icon: "custom-briefcase",
    title: "Företagsinformation",
  },
];

const alert = ref({
  message: "",
  show: false,
  type: "",
});

watchEffect(fetchData);

async function fetchData() {
  isRequestOngoing.value = true;

  userData.value = JSON.parse(localStorage.getItem("user_data") || "null");
  data.value = await authStores.company();
  role.value = userData.value.roles[0].name;

  user_id.value = userData.value.id;
  email.value = userData.value.email;
  name.value = userData.value.name;
  last_name.value = userData.value.last_name;
  phone.value = userData.value.user_detail.personal_phone;
  address.value = userData.value.user_detail.personal_address;

  //company
  form.value.company =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.company
      : userData.value.user_detail.company;
  form.value.organization_number =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.organization_number
      : userData.value.user_detail.organization_number;
  form.value.link =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.link
      : userData.value.user_detail.link;
  form.value.address =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.address
      : userData.value.user_detail.address;
  form.value.street =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.street
      : userData.value.user_detail.street;
  form.value.postal_code =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.postal_code
      : userData.value.user_detail.postal_code;
  form.value.phone =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.phone
      : userData.value.user_detail.phone;

  //bank
  form.value.bank =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.bank
      : userData.value.user_detail.bank;
  form.value.account_number =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.account_number
      : userData.value.user_detail.account_number;

  form.value.iban =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.iban
      : userData.value.user_detail.iban;
  form.value.iban_number =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.iban_number
      : userData.value.user_detail.iban_number;
  form.value.bic =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.bic
      : userData.value.user_detail.bic;
  form.value.plus_spin =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.plus_spin
      : userData.value.user_detail.plus_spin;
  form.value.swish =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.swish
      : userData.value.user_detail.swish;
  form.value.vat =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.vat
      : userData.value.user_detail.vat;

  form.value.payout_number = 
    role.value === 'User' 
      ? userData.value.supplier.boss?.payout_number 
      : userData.value.supplier?.payout_number 

  logo.value =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.logo !== null
        ? themeConfig.settings.urlStorage +
          userData.value.supplier.boss.user.user_detail.logo
        : null
      : userData.value.user_detail.logo !== null
      ? themeConfig.settings.urlStorage + userData.value.user_detail.logo
      : null;
  logoCropped.value =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.logo !== null
        ? await fetchImageAsBlob(
            themeConfig.settings.urlStorage +
              userData.value.supplier.boss.user.user_detail.logo
          )
        : null
      : userData.value.user_detail.logo !== null
      ? await fetchImageAsBlob(
          themeConfig.settings.urlStorage + userData.value.user_detail.logo
        )
      : null;

  signature.value =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.img_signature !== null
        ? themeConfig.settings.urlStorage +
          userData.value.supplier.boss.user.user_detail.img_signature
        : null
      : userData.value.user_detail.img_signature !== null
      ? themeConfig.settings.urlStorage +
        userData.value.user_detail.img_signature
      : null;
  signatureCropped.value =
    role.value === "User"
      ? userData.value.supplier.boss.user.user_detail.img_signature !== null
        ? await fetchImageAsBlob(
            themeConfig.settings.urlStorage +
              userData.value.supplier.boss.user.user_detail.img_signature
          )
        : null
      : userData.value.user_detail.img_signature !== null
      ? await fetchImageAsBlob(
          themeConfig.settings.urlStorage +
            userData.value.user_detail.img_signature
        )
      : null;

  avatarOld.value = userData.value.avatar;
  avatar.value = userData.value.avatar;

  isRequestOngoing.value = false;
}

const fetchImageAsBlob = async (url) => {
  const response = await fetch(
    themeConfig.settings.urlbase + "proxy-image?url=" + url
  );
  const blob = await response.blob();
  return URL.createObjectURL(blob);
};

const formatOrgNumber = () => {
  let numbers = form.value.organization_number.replace(/\D/g, "");
  if (numbers.length > 4) {
    numbers = numbers.slice(0, -4) + "-" + numbers.slice(-4);
  }
  form.value.organization_number = numbers;
};

const resetLogo = () => {
  logoCropped.value = null;
  logoOld.value = null;
};

const resetAvatar = () => {
  avatar.value = null;
};

const handleSubmit = () => {
  return role.value === "Supplier" || role.value === "User"
    ? onSubmit()
    : submitCompleteProfile();
};

const submitCompleteProfile = async () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      let formData = new FormData();

      formData.append("user_id", user_id.value);
      formData.append("email", email.value);
      formData.append("name", name.value);
      formData.append("last_name", last_name.value);
      formData.append("personal_phone", phone.value);
      formData.append("personal_address", address.value);
      formData.append("image", avatarOld.value);

      formData.append("logo", logoOld.value);

      formData.append("company", form.value.company);
      formData.append("organization_number", form.value.organization_number);
      formData.append("address", form.value.address);
      formData.append("street", form.value.street);
      formData.append("postal_code", form.value.postal_code);
      formData.append("phone", form.value.phone);
      formData.append("link", form.value.link);
      formData.append("bank", form.value.bank);
      formData.append("iban", form.value.iban);
      formData.append("account_number", form.value.account_number);
      formData.append("iban_number", form.value.iban_number);
      formData.append("bic", form.value.bic);
      formData.append("plus_spin", form.value.plus_spin);
      formData.append("swish", form.value.swish);
      formData.append("vat", form.value.vat);

      isRequestOngoing.value = true;

      profileStores
        .updateData(formData)
        .then((response) => {
          window.scrollTo(0, 0);

          alert.value.type = "success";
          alert.value.message = "Uppgifterna har sparats. Sidan laddas om automatiskt för att visa ändringarna.";
          alert.value.show = true;

          localStorage.setItem("user_data", JSON.stringify(response.user_data));

          setTimeout(() => {
            (alert.value.show = false), (alert.value.message = "");
            location.reload();
          }, 1000);

          isRequestOngoing.value = false;
        })
        .catch((error) => {
          alert.value.type = "error";
          alert.value.show = true;
          alert.value.message = "Ett fel har inträffat...! (Serverfel)";

          isRequestOngoing.value = false;

          setTimeout(() => {
            (alert.value.show = false), (alert.value.message = "");
          }, 1000);
        });
    }
  });
};

const onSubmit = async () => {
  if (currentTab.value === 0) {
    const isTab0Valid = await validateTab0();
    if (isTab0Valid) {
      currentTab.value = 1;
    } else {
      alert.value.type = "error";
      alert.value.message = "Fyll i alla obligatoriska uppgifter innan du fortsätter.";
      alert.value.show = true;
      await refVForm.value?.validate();
      setTimeout(() => {
        alert.value.show = false;
        alert.value.message = "";
      }, 1000);
    }
    return;
  }

  if (currentTab.value === 1) {
    const [isTab0Valid, isTab1Valid] = await Promise.all([
      validateTab0(),
      validateTab1(),
    ]);

    if (isTab0Valid && isTab1Valid) {
      await submitCompleteProfile();
    } else {
      alert.value.type = "error";
      alert.value.message = "Fyll i alla obligatoriska uppgifter innan du fortsätter.";
      alert.value.show = true;
      setTimeout(() => {
        alert.value.show = false;
        alert.value.message = "";
      }, 1000);
    }
  }
};

const onImageSelected = (event) => {
  const file = event.target.files[0];

  if (!file) return;
  // avatarOld.value = file

  URL.createObjectURL(file);

  resizeImage(file, 400, 400, 0.9).then(async (blob) => {
    avatarOld.value = blob;
    let r = await blobToBase64(blob);
    avatar.value = "data:image/jpeg;base64," + r;
  });
};

const onLogoSelected = (event) => {
  const file = event.target.files[0];

  if (!file) return;
  // logoOld.value = file

  URL.createObjectURL(file);

  resizeImage(file, 1200, 1200, 1).then(async (blob) => {
    let r = await blobToBase64(blob);
    logoCropped.value = "data:image/jpeg;base64," + r;
  });
};

const resizeImage = function (file, maxWidth, maxHeight, quality) {
  return new Promise((resolve, reject) => {
    const img = new Image();

    img.src = URL.createObjectURL(file);
    img.onload = () => {
      const canvas = document.createElement("canvas");
      const ctx = canvas.getContext("2d");

      let width = img.width;
      let height = img.height;

      if (maxWidth && width > maxWidth) {
        height *= maxWidth / width;
        width = maxWidth;
      }

      if (maxHeight && height > maxHeight) {
        width *= maxHeight / height;
        height = maxHeight;
      }

      canvas.width = width;
      canvas.height = height;

      ctx.drawImage(img, 0, 0, width, height);

      canvas.toBlob(
        (blob) => {
          resolve(blob);
        },
        file.type,
        quality
      );
    };
    img.onerror = (error) => {
      reject(error);
    };
  });
};

const blobToBase64 = (blob) => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();

    reader.readAsDataURL(blob);
    reader.onload = () => {
      resolve(reader.result.split(",")[1]);
    };
    reader.onerror = (error) => {
      reject(error);
    };
  });
};

const onCropChange = (coordinates) => {
  // console.log('coordinates', coordinates)
};

const cropImage = async () => {
  if (cropper.value) {
    const result = cropper.value.getResult({
      mime: "image/png",
      quality: 1,
      fillColor: "transparent",
    });
    const blob = dataURLtoBlob(result.canvas.toDataURL("image/png"));

    logoOld.value = blob;

    let formData = new FormData();

    formData.append("logo", logoOld.value);

    isConfirmChangeLogoVisible.value = false;
    isRequestOngoing.value = true;

    profileStores
      .updateLogo(formData)
      .then(async (response) => {
        window.scrollTo(0, 0);

        isRequestOngoing.value = false;
        localStorage.setItem("user_data", JSON.stringify(response.user_data));

        let r = await blobToBase64(blob);
        logo.value = "data:image/jpeg;base64," + r;
      })
      .catch((error) => {
        isRequestOngoing.value = false;
        console.log("error", error);
        advisor.value.type = "error";
        advisor.value.show = true;
        advisor.value.message = "Ett serverfel uppstod. Försök igen.";
        emit("alert", advisor);

        setTimeout(() => {
          (advisor.value.show = false), (advisor.value.message = "");
          emit("alert", advisor);
        }, 5000);
      });
  }
};

const cropSignatureImage = async () => {
  if (cropperSignature.value) {
    // Usamos la nueva referencia del cropper
    const result = cropperSignature.value.getResult({
      mime: "image/png",
      quality: 1,
      fillColor: "transparent",
    });
    const blob = dataURLtoBlob(result.canvas.toDataURL("image/png"));

    signatureOld.value = blob; // Guardamos el blob en la variable de la firma

    let formData = new FormData();

    formData.append("img_signature", signatureOld.value); // La clave debe ser 'img_signature'

    isConfirmChangeSignatureVisible.value = false;
    isRequestOngoing.value = true;

    // IMPORTANTE: Asumo que tendrás una nueva acción en tu store llamada 'updateSignature'
    // Deberás crearla en tu store de Pinia, similar a 'updateLogo'.
    profileStores
      .updateSignature(formData)
      .then(async (response) => {
        window.scrollTo(0, 0);

        isRequestOngoing.value = false;
        localStorage.setItem("user_data", JSON.stringify(response.user_data));

        let r = await blobToBase64(blob);
        signature.value = "data:image/jpeg;base64," + r; // Actualizamos la imagen visible
      })
      .catch((error) => {
        isRequestOngoing.value = false;
        console.log("error", error);
        advisor.value.type = "error";
        advisor.value.show = true;
        advisor.value.message = "Ett serverfel uppstod. Försök igen.";
        emit("alert", advisor);

        setTimeout(() => {
          (advisor.value.show = false), (advisor.value.message = "");
          emit("alert", advisor);
        }, 5000);
      });
  }
};

const openSignaturePadDialog = () => {
  isSignaturePadDialogVisible.value = true;
  nextTick(() => {
    const canvas = signaturePadCanvas.value;
    if (canvas) {
      signaturePadInstance.value = new SignaturePad(canvas, {
        backgroundColor: "rgba(255, 255, 255, 0)", // Fondo transparente
        penColor: "rgb(0, 0, 0)",
      });
      const ratio = Math.max(window.devicePixelRatio || 1, 1);
      canvas.width = canvas.offsetWidth * ratio;
      canvas.height = canvas.offsetHeight * ratio;
      canvas.getContext("2d").scale(ratio, ratio);
      signaturePadInstance.value.clear();
    }
  });
};

const saveSignatureFromPad = async () => {
  if (signaturePadInstance.value && !signaturePadInstance.value.isEmpty()) {
    // Obtener la firma como imagen PNG con fondo transparente
    const signatureDataUrl = signaturePadInstance.value.toDataURL("image/png");

    // Convertir la DataURL a un Blob (archivo en memoria)
    const blob = dataURLtoBlob(signatureDataUrl); // Reutilizamos tu función existente!

    // Crear FormData y añadir el blob como si fuera un archivo subido
    const formData = new FormData();
    formData.append("img_signature", blob, "signature.png"); // El backend lo recibirá como un archivo

    // Cerramos el diálogo y mostramos el spinner
    isSignaturePadDialogVisible.value = false;
    isRequestOngoing.value = true;

    // Llamamos a la misma acción de Pinia que usa la subida de archivo
    profileStores
      .updateSignature(formData)
      .then(async (response) => {
        window.scrollTo(0, 0);
        localStorage.setItem("user_data", JSON.stringify(response.user_data));

        // Actualizamos la imagen visible en la página con la nueva firma
        signature.value = signatureDataUrl;

        // Opcional: mostrar un mensaje de éxito
      })
      .catch((error) => {
        console.log("error", error);
        advisor.value.type = "error";
        advisor.value.show = true;
        advisor.value.message = "Ett serverfel uppstod. Försök igen.";
        emit("alert", advisor);
        setTimeout(() => {
          advisor.value.show = false;
          advisor.value.message = "";
          emit("alert", advisor);
        }, 5000);
      })
      .finally(() => {
        isRequestOngoing.value = false;
      });
  } else {
    // Si el lienzo está vacío, simplemente cierra el diálogo
    isSignaturePadDialogVisible.value = false;
  }
};

const clearSignaturePad = () => {
  if (signaturePadInstance.value) {
    signaturePadInstance.value.clear();
  }
};

const resetSignature = () => {
  signatureCropped.value = null;
  signatureOld.value = null;
};

const onSignatureImageSelected = (event) => {
  const file = event.target.files[0];

  if (!file) return;

  URL.createObjectURL(file);

  resizeImage(file, 1200, 1200, 1) // Reutilizamos tu función resizeImage
    .then(async (blob) => {
      let r = await blobToBase64(blob);
      signatureCropped.value = "data:image/jpeg;base64," + r; // Actualizamos la variable de la firma
    });
};

const dataURLtoBlob = (dataURL) => {
  const [header, base64] = dataURL.split(",");
  const mimeMatch = header.match(/:(.*?);/);
  const mime = mimeMatch ? mimeMatch[1] : "image/png";
  const binary = atob(base64);
  const len = binary.length;
  const u8arr = new Uint8Array(len);
  for (let i = 0; i < len; i++) {
    u8arr[i] = binary.charCodeAt(i);
  }
  return new Blob([u8arr], { type: mime });
};
</script>

<template>
  <VSnackbar
    v-model="alert.show"
    transition="scroll-y-reverse-transition"
    :location="snackbarLocation"
    :color="alert.type"
    class="snackbar-alert"
  >
    {{ alert.message }}
  </VSnackbar> 
  <VDialog v-model="isRequestOngoing" width="auto" persistent>
    <VProgressCircular indeterminate color="primary" class="mb-0" />
  </VDialog>
  <div class="d-flex justify-center m-0 p-0 bg-white">
    <div class="d-none d-md-flex p-0">
      <div
        style="
          position: sticky;
          top: 0;
          height: 100vh;
          width: auto;
          overflow: hidden;
        "
      >
        <img :src="background" class="login-background" />
        <div class="d-flex logo-box">
          <img :src="logo_gradient" width="121" height="40" />
        </div>
      </div>
    </div>
    <div class="d-flex flex-column gap-4 gap-md-8 px-md-8 pa-0 bg-white flex-1">
      <div class="sticky-form">
        <div class="black-logo-box">
          <img :src="logo_gradient" width="121" height="40" />
        </div>

        <div
          class="d-flex flex-md-column align-center justify-center gap-6 pa-4 pa-md-6 profile-title-box"
        >
          <VIcon
            icon="custom-f-upload-picture"
            size="88"
            class="mx-auto"
          ></VIcon>

          <h2 class="profile-title">Kom igång - fyll i din profil</h2>
        </div>
        <VTabs
          v-if="role === 'Supplier' || role === 'User'"
          v-model="controlledTab"
          grow
          :show-arrows="false"
          class="profile-tabs"
        >
          <VTab v-for="tab in tabs" :key="tab.icon">
            <VIcon size="24" :icon="tab.icon" />
            <span>{{ tab.title }}</span>
          </VTab>
        </VTabs>
      </div>

      <VForm
        ref="refVForm"
        @submit.prevent="handleSubmit"
        class="auth-form mx-auto d-flex flex-column gap-4"
      >
        <template v-if="role === 'Supplier' || role === 'User'">
          <VWindow
            v-model="controlledTab"
            class="disable-tab-transition"
            :touch="false"
          >
            <VWindowItem class="d-flex flex-column gap-4">
              <div class="d-flex flex-column align-center justify-center gap-4">
                <VBtn
                  class="upload-avatar-btn"
                  @click="$refs.avatarInput.click()"
                  style="padding: 0; min-width: auto"
                >
                  <VAvatar class="avatar-box" size="128">
                    <VImg v-if="avatar" :src="avatar" />
                    <template v-else>
                      <VIcon icon="custom-upload" color="#1C2925" size="40" />
                    </template>
                  </VAvatar>
                </VBtn>

                <input
                  ref="avatarInput"
                  type="file"
                  accept="image/png, image/jpeg, image/bmp"
                  @change="onImageSelected"
                  style="display: none"
                />
              </div>
              <p class="text-center label-upload-input mb-0">
                Ladda upp ett foto
              </p>
              <div class="form-field d-flex flex-column gap-1">
                <label>Namn*</label>
                <VTextField v-model="name" :rules="[requiredValidator]" />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Efternamn*</label>
                <VTextField v-model="last_name" :rules="[requiredValidator]" />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>E-post</label>
                <VTextField
                  v-model="email"
                  type="email"
                  :rules="[requiredValidator, emailValidator]"
                  disabled
                />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Telefon*</label>
                <VTextField
                  v-model="phone"
                  type="tel"
                  placeholder="+(XX) XXXXXXXXX"
                  :rules="[phoneValidator, requiredValidator]"
                />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Adress*</label>
                <VTextField
                  v-model="address"
                  :rules="[requiredValidator]"
                />
              </div>
            </VWindowItem>

            <VWindowItem class="d-flex flex-column gap-4">
              <div class="d-flex flex-column align-center justify-center gap-4">
                <VBtn
                  class="upload-avatar-btn"
                  @click="isConfirmChangeLogoVisible = true"
                  style="padding: 0; min-width: auto"
                  :disabled="role === 'User'"
                >
                  <VAvatar class="avatar-box" size="128">
                    <VImg v-if="logo" :src="logo" />
                    <template v-else>
                      <VIcon icon="custom-upload" color="#1C2925" size="40" />
                    </template>
                  </VAvatar>
                </VBtn>

                <input
                  ref="avatarInput"
                  type="file"
                  accept="image/png, image/jpeg, image/bmp"
                  @change="onImageSelected"
                  style="display: none"
                />
              </div>

              <p
                class="text-center label-upload-input"
                v-if="role === 'Supplier'"
              >
                Ladda upp ditt företags logotyp
              </p>

              <div class="form-field d-flex flex-column gap-1">
                <label>Företagsnamn*</label>
                <VTextField
                  :disabled="role === 'User'"
                  v-model="form.company"
                  :rules="[requiredValidator]"
                />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Organisationsnummer*</label>
                <VTextField
                  v-model="form.organization_number"
                  :disabled="role === 'Supplier' || role === 'User'"
                  :rules="[requiredValidator, minLengthDigitsValidator(10)]"
                  minLength="11"
                  maxlength="11"
                  @input="formatOrgNumber()"
                />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Adress*</label>
                <VTextField
                  :disabled="role === 'User'"
                  v-model="form.address"
                  :rules="[requiredValidator]"
                />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Postnummer*</label>
                <VTextField
                  :disabled="role === 'User'"
                  v-model="form.postal_code"
                  :rules="[requiredValidator]"
                />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Stad*</label>
                <VTextField
                  :disabled="role === 'User'"
                  v-model="form.street"
                  :rules="[requiredValidator]"
                />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Telefon*</label>
                <VTextField
                  :disabled="role === 'User'"
                  v-model="form.phone"
                  :rules="[requiredValidator, phoneValidator]"
                />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Hemsida*</label>
                <VTextField
                  :disabled="role === 'User'"
                  v-model="form.link"
                  :rules="[urlValidator]"
                />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Bank*</label>
                <VTextField
                  :disabled="role === 'User'"
                  v-model="form.bank"
                  :rules="[requiredValidator]"
                />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Bankgiro</label>
                <VTextField :disabled="role === 'User'" v-model="form.iban" />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Namn*</label>
                <VTextField
                  :disabled="role === 'User'"
                  v-model="form.account_number"
                  :rules="[requiredValidator]"
                  label="Kontonummer"
                />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Iban nummer</label>
                <VTextField
                  :disabled="role === 'User'"
                  v-model="form.iban_number"
                />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>BIC</label>
                <VTextField :disabled="role === 'User'" v-model="form.bic" />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Plusgiro</label>
                <VTextField
                  :disabled="role === 'User'"
                  v-model="form.plus_spin"
                />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Swish</label>
                <VTextField
                  :disabled="role === 'User'"
                  v-model="form.swish"
                  :rules="[phoneValidator]"
                />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Vat</label>
                <VTextField :disabled="role === 'User'" v-model="form.vat" />
              </div>
              <div class="form-field d-flex flex-column gap-1">
                <label>Payout Number*</label>
                <VTextField
                  v-model="form.payout_number"
                  disabled
                />
              </div>
              <div
                class="form-field form-field-checkbox d-flex align-center gap-4"
              >
                <VCheckbox
                  v-model="acceptPrivacy"
                  :rules="[
                    (v) =>
                      !!v ||
                      'Du måste godkänna sekretesspolicyn och databehandlingen.',
                  ]"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
                />
                <label
                  >Jag godkänner sekretesspolicyn och databehandlingen.</label
                >
              </div>
              <div
                class="form-field d-flex flex-column gap-1"
                v-if="role !== 'User'"
              >
                <div class="d-flex align-center gap-4">
                  <VImg :src="signature" class="signature-image" />
                  <div class="d-flex flex-column gap-2 flex-1">
                    <VBtn
                      class="btn-light"
                      @click="isConfirmChangeSignatureVisible = true"
                    >
                      <VIcon icon="custom-upload" size="24" />
                      Ladda upp fil
                    </VBtn>
                    <VBtn class="btn-ghost" @click="openSignaturePadDialog">
                      <VIcon icon="custom-pencil" size="24" />
                      Rita signatur
                    </VBtn>
                  </div>
                </div>
              </div>
            </VWindowItem>
          </VWindow>
          <div>
            <VBtn type="submit" class="btn-gradient w-100 mt-4 mb-10">
              {{ currentTab === 1 ? "Kom igång" : " Nästa" }}
              <VProgressCircular
                  v-if="isRequestOngoing"
                  indeterminate
                  color="#fff"
                />
            </VBtn>
          </div>
        </template>
        <template v-else>
          <div class="d-flex flex-column align-center justify-center gap-4">
            <VBtn
              class="upload-avatar-btn"
              @click="$refs.avatarInput.click()"
              style="padding: 0; min-width: auto"
            >
              <VAvatar class="avatar-box" size="128">
                <VImg v-if="avatar" :src="avatar" />
                <template v-else>
                  <VIcon icon="custom-upload" color="#1C2925" size="40" />
                </template>
              </VAvatar>
            </VBtn>

            <input
              ref="avatarInput"
              type="file"
              accept="image/png, image/jpeg, image/bmp"
              @change="onImageSelected"
              style="display: none"
            />
          </div>

          <p class="text-center label-upload-input mb-0">
            Ladda upp ett foto
          </p>

          <!-- 👉 Upload Photo -->
          <div class="d-none flex-column justify-center gap-4">
            <div class="d-flex flex-wrap gap-2">
              <VFileInput
                label="Avatar"
                accept="image/png, image/jpeg, image/bmp"
                placeholder="Avatar"
                prepend-icon="tabler-camera"
                @change="onImageSelected"
                @click:clear="resetAvatar"
              />
            </div>
            <p class="text-body-1 mb-0">Tillåtna format JPG, GIF, PNG.</p>
          </div>

          <div class="form-field d-flex flex-column gap-1">
            <label>Namn*</label>
            <VTextField v-model="name" :rules="[requiredValidator]" />
          </div>
          <div class="form-field d-flex flex-column gap-1">
            <label>Efternamn*</label>
            <VTextField v-model="last_name" :rules="[requiredValidator]" />
          </div>

          <div class="form-field d-flex flex-column gap-1">
            <label>E-post*</label>
            <VTextField
              v-model="email"
              type="email"
              :rules="[requiredValidator, emailValidator]"
              disabled
            />
          </div>
          <div class="form-field d-flex flex-column gap-1">
            <label>Telefon*</label>
            <VTextField
              v-model="phone"
              type="tel"
              placeholder="+(XX) XXXXXXXXX"
              :rules="[phoneValidator, requiredValidator]"
            />
          </div>
          <div class="form-field d-flex flex-column gap-1">
            <label>Adress*</label>
            <VTextField
              v-model="address"
              :rules="[requiredValidator]"
            />
          </div>
          <!-- 👉 Form Actions -->
          <div>
            <VBtn type="submit" class="btn-gradient w-100 mt-4 mb-10"> Kom igång 
              <VProgressCircular
                  v-if="isRequestOngoing"
                  indeterminate
                  color="#fff"
                />
            </VBtn>
          </div>
        </template>
      </VForm>
    </div>
  </div>

  <!-- 👉 Confirm change logo -->
  <VDialog
    v-model="isConfirmChangeLogoVisible"
    persistent
    class="signature-dialog"
  >
    <!-- Dialog close btn -->

    <DialogCloseBtn
      @click="isConfirmChangeLogoVisible = !isConfirmChangeLogoVisible"
    />

    <!-- Dialog Content -->
    <VCard>
      <VCardText class="without-padding v-card-custom-title">
        Byt logotyp
      </VCardText>
      <VCardText class="d-flex flex-column gap-2 without-padding">
        <VRow>
          <VCol cols="12" md="12">
            <Cropper
              v-if="logoCropped"
              ref="cropper"
              class="cropper-container"
              preview-class="cropper-preview"
              background-class="cropper-background"
              :src="logoCropped"
              :stencil-props="{
                previewClass: 'cropper-preview-circle',
              }"
              @change="onCropChange"
            />
          </VCol>
          <VCol cols="12" md="12">
            <div class="form-field d-flex flex-column gap-1 mb-2">
              <label>Logotyp</label>
              <VFileInput
                v-model="filename"
                accept="image/png, image/jpeg, image/bmp, image/webp"
                @change="onLogoSelected"
                @click:clear="resetLogo"
                prepend-icon=""
              />
            </div>
          </VCol>
        </VRow>
      </VCardText>

      <VCardText class="d-flex justify-end gap-4 btn-box">
        <VBtn class="btn-ghost" @click="isConfirmChangeLogoVisible = false">
          Avbryt
        </VBtn>
        <VBtn class="btn-gradient" @click="cropImage">Acceptera & Spara</VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- 👉 Confirm change Signature -->
  <VDialog
    v-model="isConfirmChangeSignatureVisible"
    persistent
    class="signature-dialog"
  >
    <!-- Dialog close btn -->

    <DialogCloseBtn
      @click="
        isConfirmChangeSignatureVisible = !isConfirmChangeSignatureVisible
      "
    />
    <!-- Dialog Content -->
    <VCard>
      <VCardText class="without-padding v-card-custom-title">
        <VIcon icon="custom-signature" class="mr-4" size="32"></VIcon>
        Byt signatur
      </VCardText>
      <VCardText class="d-flex flex-column gap-2 without-padding">
        <VRow>
          <VCol cols="12" md="12">
            <Cropper
              v-if="signatureCropped"
              :ref="(el) => (cropperSignature = el)"
              class="cropper-container"
              preview-class="cropper-preview"
              background-class="cropper-background"
              :src="signatureCropped"
              :stencil-props="{
                aspectRatio: 16 / 9, // Puedes ajustar el aspect ratio si lo necesitas
              }"
            />
          </VCol>
          <VCol cols="12" md="12">
            <div class="form-field d-flex flex-column gap-1">
              <label>Firm</label>
              <VFileInput
                v-model="signatureFilename"
                class="mb-2"
                accept="image/png, image/jpeg, image/bmp, image/webp"
                prepend-icon=""
                @change="onSignatureImageSelected"
                @click:clear="resetSignature"
              />
            </div>
          </VCol>
        </VRow>
      </VCardText>

      <VCardText class="d-flex justify-end gap-4 btn-box">
        <VBtn
          class="btn-ghost"
          @click="isConfirmChangeSignatureVisible = false"
        >
          Avbryt
        </VBtn>
        <VBtn class="btn-gradient" @click="cropSignatureImage"
          >Acceptera & Spara</VBtn
        >
      </VCardText>
    </VCard>
  </VDialog>

  <!-- ======================================================= -->
  <!-- DIÁLOGO PARA DIBUJAR LA FIRMA DEL SUPPLIER -->
  <!-- ======================================================= -->
  <VDialog
    v-model="isSignaturePadDialogVisible"
    persistent
    class="signature-dialog"
  >
    <VCard>
      <VCardText class="without-padding v-card-custom-title">
        <VIcon icon="custom-signature" class="mr-4" size="32"></VIcon>Rita din
        signatur
      </VCardText>
      <VCardText class="without-padding">
        <div class="signature-pad-wrapper">
          <canvas ref="signaturePadCanvas"></canvas>
        </div>
      </VCardText>
      <VCardText class="d-flex justify-end gap-4 btn-box">
        <VBtn class="btn-ghost" @click="isSignaturePadDialogVisible = false">
          Avbryt
        </VBtn>
        <VBtn class="btn-light" @click="clearSignaturePad">Rensa</VBtn>
        <VBtn class="btn-gradient" @click="saveSignatureFromPad"
          >Acceptera & Spara</VBtn
        >
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style lang="scss">

.sticky-form {
  position: sticky;
  top: 0;
  z-index: 10;
  background: #ffffff;
}

.individual-scroll {
  overflow-y: auto;
  height: 100vh;
}

.login-background {
  height: 100vh;
  width: auto;
  object-fit: contain;
  object-position: top;
}

.black-logo-box {
  width: 100%;
  background-color: #1c2925;
  padding: 24px;
  display: none;

  @media (max-width: 991px) {
    display: flex;
    justify-content: center;
  }
}

.m-0 {
  margin: 0;
}

@media (max-width: 991px) {

  .profile-title-box {

    .v-icon {
      margin: 0 !important;
      flex: none !important;
    }
    .profile-title {
      font-weight: 700;
      font-size: 32px;
      line-height: 100%;
      text-align: left;
    }
  }
}

.profile-title {
  font-weight: 700;
  font-size: 32px;
  line-height: 100%;
  text-align: center;
  color: #454545;
}

.label-upload-input {
  font-weight: 400;
  font-size: 16px;
  line-height: 24px;
  text-align: center;
  color: #878787;
}

.signature-image {
  flex: 1 1;
  width: 200px;
  height: 104px;
  border-radius: 8px;
  border: solid 1px #e7e7e7;
  background-color: #f6f6f6;
}

.v-tabs.profile-tabs {
  .v-btn {
    .v-btn__content {
      font-size: 14px !important;
      color: #454545;
    }
  }
}

@media (max-width: 991px) {
  .auth-form {
    max-width: 90% !important;
    margin-bottom: 32px !important;
  }
}

.signature-dialog {
  max-width: 500px;
  border-radius: 16px;
  gap: 32px;
  padding: 24px;

  .v-overlay__content {
    width: 100% !important;
    max-width: none !important;
    margin: 0;

    .v-dialog-close-btn {
      top: 16px !important;
      right: 24px;
      transform: none !important;
      height: 16px !important;
      width: 16px !important;
      padding: 0px !important;
    }

    .v-card {
      .v-card-text {
        padding: 24px 24px 24px !important;

        &.without-padding {
          padding: 24px 24px 0px !important;
        }

        &.v-card-custom-title {
          font-weight: 600;
          font-size: 24px;
          line-height: 100%;
          color: #5d5d5d;
        }

        @media (max-width: 991px) {
          &.btn-box {
            flex-direction: column-reverse;
            gap: 8px;
          }
        }
      }
    }
  }
}

.justify-buttons {
  justify-content: right !important;

  @media (max-width: 767px) {
    justify-content: center !important;
  }
}
</style>

<style scoped>
:deep(.vue-simple-handler) {
  background: #57F287 !important;
}
:deep(.cropper-preview-circle) {
  border: dashed 1px #57F287;
}
:deep(.cropper-background),
:deep(.vue-advanced-cropper__foreground) {
  background-color: transparent !important;
}

.cropper-container {
  width: 100%;
  height: 232px;
  background-color: #f6f6f6;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e7e7e7;
}

.cropper-preview {
  width: 100px;
  height: 100px;
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-top: 1rem;
}

.banner-img {
  width: 100%;
  height: 170px;
}

.info-logo-store {
  display: flex;
  padding-left: 14rem !important;
}

.info-store {
  padding-left: 0;
  padding-right: 0;
  padding-top: 24px;
  padding-bottom: 24px;
}

.logo-store {
  top: 10%;
  left: 3%;
  z-index: 9999;
  position: absolute;
}

.logo-store-img {
  width: 173.96px;
  height: 173.96px;
  max-width: 173.96px;
  border-radius: 16px;
  background-color: #f5f5f5;
}

.tw-bg-tertiary {
  opacity: 1 !important;
  background-color: #0a1b33 !important;
}

.store-name {
  font-size: 24px;
  font-style: normal;
  font-weight: 600;
  line-height: 24px;
  color: white;
}

.store-address {
  font-size: 16px;
  font-style: normal;
  font-weight: 600;
  line-height: 16px;
  color: white;
  margin-inline-start: 20px;
}

.signature-pad-wrapper {
  border: 1px solid #e7e7e7;
  border-radius: 8px;
  background-color: #f6f6f6;
}

.signature-pad-wrapper canvas {
  width: 100%;
  height: 232px;
  display: block;
  cursor: crosshair;
}

@media (max-width: 776px) {
  .v-tabs.profile-tabs {
    .v-icon {
      display: none !important;
    }
    .v-btn {
      .v-btn__content {
        white-space: break-spaces;
      }
    }
  }

  .cropper-container {
    height: 250px;
  }

  .info-logo-store {
    margin-top: 8%;
    margin-bottom: 2%;
    flex-direction: column;
    padding-left: 1rem !important;
  }

  .logo-store {
    top: 5%;
    left: 5%;
  }

  .info-store {
    padding-left: 0;
    padding-right: 0;
    padding-top: 0;
    padding-bottom: 0;
  }

  .store-address {
    margin-inline-start: 0;
  }
}
</style>

<route lang="yaml">
meta:
  layout: blank
  action: view
  subject: Auth
  parar: true
</route>
