import fs from 'fs';
import { resolve } from 'path';

// Check if generated Tailwind config exists, otherwise use default
const tailwindConfig = fs.existsSync(resolve(process.cwd(), 'tailwind.config.generated.js'))
  ? './tailwind.config.generated.js'
  : './tailwind.config.js';

export default {
  plugins: {
    tailwindcss: { config: tailwindConfig },
    autoprefixer: {},
  },
};
