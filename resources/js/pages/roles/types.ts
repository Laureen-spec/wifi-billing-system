export interface Role {
    id: number;
    name: string;
    label: string;
    editable: boolean;
    permissions_count?: number;
    users?: Array<{
        id: number;
        name: string;
    }>;
}

export interface RolesIndexProps {
    roles: {
        data: Role[];
        links: any[];
        meta: any;
    };
    auth: {
        user: {
            permissions?: string[];
        };
    };
    [key: string]: any;
}

export interface RoleFilters {
    name: string;
    [key: string]: any;
}

export interface Permission {
    id: number;
    name: string;
    label: string;
    module: string;
    add_on: string;
    guard_name: string;
}

export type PermissionGroups = Record<string, Record<string, Permission[]>>;

export interface RoleCreateProps {
    permissions: PermissionGroups;
    [key: string]: any;
}

export interface RoleEditProps {
    role: Role & { editable: boolean };
    permissions: PermissionGroups;
    rolePermissions: string[];
    [key: string]: any;
}

export interface RoleModalState {
    isOpen: boolean;
    mode: string;
    data: Role | null;
}
