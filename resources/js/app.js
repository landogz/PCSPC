import './bootstrap';
import { initAuthModule } from './modules/auth';
import { initLayoutModule } from './modules/layout';
import { initDocsModule } from './modules/docs';

initLayoutModule();
initAuthModule();
initDocsModule();
