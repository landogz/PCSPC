import './bootstrap';
import { initAuthModule } from './modules/auth';
import { initLayoutModule } from './modules/layout';
import { initDocsModule } from './modules/docs';
import { initSecurityModule } from './modules/security';
import { initAuditModule } from './modules/audit';
import { initAdministrationModule } from './modules/administration';
import { initDepartmentsModule } from './modules/departments';
import { initEmployeesModule } from './modules/employees';

initLayoutModule();
initAuthModule();
initDocsModule();
initSecurityModule();
initAuditModule();
initAdministrationModule();
initDepartmentsModule();
initEmployeesModule();
