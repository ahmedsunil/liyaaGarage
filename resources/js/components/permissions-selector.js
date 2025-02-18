export default function permissionsSelector({
  state,
  modelPermissions,
  selectedModelPermissions,
}) {
    return {
        state,
        modelPermissions,
        selectedModelPermissions,
        searchByModels: '',
        searchByPermissions: '',
        displayedModels: [],
        displayedModelPermissions: [],

        init: function () {
            // set the state to empty array
            this.state = this.selectedModelPermissions ?? [];
            this.displayedModels = this.modelPermissions;
        },

        searchModels() {
            this.displayedModels = this.modelPermissions.filter(modelPermission => {
                return modelPermission.model.toLowerCase().includes(this.searchByModels.toLowerCase());
            });

            this.displayedModelPermissions = this.displayedModels;
        },

        searchPermissions() {
            if (this.displayedModelPermissions.length === 0) {
                this.displayedModelPermissions = this.modelPermissions;
            }

            this.displayedModels = this.displayedModelPermissions.map(modelPermission => {
                return {
                    model: modelPermission.model,
                    permissions: modelPermission.permissions.filter(permission => {
                        return permission.name.toLowerCase().includes(this.searchByPermissions.toLowerCase());
                    })
                }
            })
        },

        updatePermission(permissionId) {
            const index = this.selectedModelPermissions.indexOf(permissionId);

            // If the permission is not in the state, add it
            if (index === -1) {
                this.selectedModelPermissions.push(permissionId);
            } else {
                // If the permission is in the state, remove it
                this.selectedModelPermissions.splice(index, 1);
            }
        },

        isChecked(permissionId) {
            return this.selectedModelPermissions.includes(permissionId);
        },

        isSearchingByModels() {
            return this.searchByModels.length > 0;
        },

        clearSearchByModels() {
            this.searchByModels = '';
            this.searchModels();
        },

        clearSearchByPermissions() {
            this.searchByPermissions = '';
            this.searchPermissions();
        },

        allPermissionsAreSelected() {
            // Count the permissions in modelPermissions
            let permissionsCount = 0;
            this.modelPermissions.forEach((modelPermission) => {
                permissionsCount += modelPermission.permissions.length;
            });

            return this.selectedModelPermissions.length === permissionsCount;
        },

        selectAllPermissions() {
            let permissions = [];
            if (this.displayedModelPermissions.length === 0) {
                this.displayedModelPermissions = this.modelPermissions;
            }

            this.displayedModelPermissions.forEach((modelPermission) => {
                modelPermission.permissions.forEach((permission) => {
                    permissions.push(permission.id);
                });
            });

            this.selectedModelPermissions = permissions;
        },

        deselectAllPermissions() {
            let permissions = [];
            this.displayedModelPermissions.forEach((modelPermission) => {
                modelPermission.permissions.forEach((permission) => {
                    permissions.push(permission.id);
                });
            });

            // remove permissions from selectedModelPermissions
            this.selectedModelPermissions = this.selectedModelPermissions.filter(permission => {
                return !permissions.includes(permission);
            });
        },

    }
}
