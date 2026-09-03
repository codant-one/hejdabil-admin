import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.bilflogg.app',
  appName: 'Bilflogg',
  webDir: 'dist',
  server: {
    androidScheme: 'https'
  }
};

export default config;