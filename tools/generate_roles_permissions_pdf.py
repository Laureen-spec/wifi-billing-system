from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import inch
from reportlab.pdfbase.pdfmetrics import stringWidth
from reportlab.platypus import Flowable, KeepTogether, PageBreak, Paragraph, Preformatted, SimpleDocTemplate, Spacer, Table, TableStyle


ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / "docs" / "StudyRoom_Connect_ERP_Roles_Permissions_Addons_Staff_Assignment_Guide.pdf"


class FlowDiagram(Flowable):
    def __init__(self, labels, width=470, box_height=34, gap=20, title=None):
        super().__init__()
        self.labels = labels
        self.width = width
        self.box_height = box_height
        self.gap = gap
        self.title = title
        self.height = box_height + 18 + (18 if title else 0)

    def wrap(self, avail_width, avail_height):
        self.width = min(self.width, avail_width)
        return self.width, self.height

    def draw(self):
        c = self.canv
        if self.title:
            c.setFillColor(colors.HexColor("#111827"))
            c.setFont("Helvetica-Bold", 9)
            c.drawString(0, self.height - 10, self.title)

        y = 4
        title_offset = 18 if self.title else 0
        y += title_offset
        count = len(self.labels)
        box_width = (self.width - (count - 1) * self.gap) / count

        for index, label in enumerate(self.labels):
            x = index * (box_width + self.gap)
            c.setStrokeColor(colors.HexColor("#2563eb"))
            c.setFillColor(colors.HexColor("#eff6ff"))
            c.roundRect(x, y, box_width, self.box_height, 5, stroke=1, fill=1)
            c.setFillColor(colors.HexColor("#111827"))
            c.setFont("Helvetica-Bold", 7.4)
            self._draw_centered(label, x + 5, y + 10, box_width - 10)

            if index < count - 1:
                start_x = x + box_width
                end_x = start_x + self.gap
                mid_y = y + self.box_height / 2
                c.setStrokeColor(colors.HexColor("#6b7280"))
                c.line(start_x + 3, mid_y, end_x - 5, mid_y)
                c.line(end_x - 5, mid_y, end_x - 10, mid_y + 4)
                c.line(end_x - 5, mid_y, end_x - 10, mid_y - 4)

    def _draw_centered(self, text, x, y, max_width):
        c = self.canv
        words = text.split()
        lines = []
        current = ""
        for word in words:
            test = (current + " " + word).strip()
            if stringWidth(test, "Helvetica-Bold", 7.4) <= max_width:
                current = test
            else:
                if current:
                    lines.append(current)
                current = word
        if current:
            lines.append(current)
        lines = lines[:2]
        start_y = y + (len(lines) - 1) * 4
        for i, line in enumerate(lines):
            text_width = stringWidth(line, "Helvetica-Bold", 7.4)
            c.drawString(x + (max_width - text_width) / 2, start_y - i * 9, line)


class StackDiagram(Flowable):
    def __init__(self, rows, width=470, title=None):
        super().__init__()
        self.rows = rows
        self.width = width
        self.title = title
        self.height = len(rows) * 42 + 18 + (18 if title else 0)

    def wrap(self, avail_width, avail_height):
        self.width = min(self.width, avail_width)
        return self.width, self.height

    def draw(self):
        c = self.canv
        y = self.height - 18
        if self.title:
            c.setFillColor(colors.HexColor("#111827"))
            c.setFont("Helvetica-Bold", 9)
            c.drawString(0, y, self.title)
            y -= 18

        for index, (left, right) in enumerate(self.rows):
            fill = colors.HexColor("#f8fafc") if index % 2 == 0 else colors.HexColor("#eef2ff")
            c.setFillColor(fill)
            c.setStrokeColor(colors.HexColor("#cbd5e1"))
            c.roundRect(0, y - 28, self.width, 30, 5, stroke=1, fill=1)
            c.setFillColor(colors.HexColor("#0f172a"))
            c.setFont("Helvetica-Bold", 8)
            c.drawString(10, y - 9, left)
            c.setFont("Helvetica", 8)
            c.drawString(170, y - 9, right)
            if index < len(self.rows) - 1:
                c.setStrokeColor(colors.HexColor("#94a3b8"))
                c.line(self.width / 2, y - 30, self.width / 2, y - 38)
                c.line(self.width / 2, y - 38, self.width / 2 - 4, y - 33)
                c.line(self.width / 2, y - 38, self.width / 2 + 4, y - 33)
            y -= 42


def build_styles():
    styles = getSampleStyleSheet()
    styles.add(
        ParagraphStyle(
            name="CoverTitle",
            parent=styles["Title"],
            fontSize=25,
            leading=31,
            alignment=TA_CENTER,
            textColor=colors.HexColor("#0f172a"),
            spaceAfter=18,
        )
    )
    styles.add(
        ParagraphStyle(
            name="CoverSub",
            parent=styles["BodyText"],
            fontSize=12,
            leading=18,
            alignment=TA_CENTER,
            textColor=colors.HexColor("#475569"),
            spaceAfter=18,
        )
    )
    styles.add(
        ParagraphStyle(
            name="SectionTitle",
            parent=styles["Heading1"],
            fontSize=17,
            leading=22,
            textColor=colors.HexColor("#0f172a"),
            spaceAfter=12,
        )
    )
    styles.add(
        ParagraphStyle(
            name="Subhead",
            parent=styles["Heading2"],
            fontSize=11,
            leading=15,
            textColor=colors.HexColor("#1e40af"),
            spaceBefore=8,
            spaceAfter=5,
        )
    )
    styles.add(
        ParagraphStyle(
            name="BodySmall",
            parent=styles["BodyText"],
            fontSize=9.2,
            leading=13,
            textColor=colors.HexColor("#1f2937"),
            spaceAfter=7,
        )
    )
    styles.add(
        ParagraphStyle(
            name="Note",
            parent=styles["BodyText"],
            fontSize=8.8,
            leading=12,
            textColor=colors.HexColor("#334155"),
            backColor=colors.HexColor("#f8fafc"),
            borderColor=colors.HexColor("#cbd5e1"),
            borderWidth=0.5,
            borderPadding=7,
            spaceBefore=6,
            spaceAfter=8,
        )
    )
    styles.add(
        ParagraphStyle(
            name="Cell",
            parent=styles["BodyText"],
            fontSize=7.7,
            leading=10,
            textColor=colors.HexColor("#111827"),
        )
    )
    styles.add(
        ParagraphStyle(
            name="HeaderCell",
            parent=styles["BodyText"],
            fontSize=7.6,
            leading=9,
            textColor=colors.white,
            alignment=TA_LEFT,
        )
    )
    return styles


def p(text, styles):
    return Paragraph(text, styles["BodySmall"])


def h(text, styles):
    return Paragraph(text, styles["SectionTitle"])


def sub(text, styles):
    return Paragraph(text, styles["Subhead"])


def bullets(items, styles):
    return KeepTogether([Paragraph(f"- {item}", styles["BodySmall"]) for item in items])


def table(data, widths, styles):
    converted = []
    for row_index, row in enumerate(data):
        style = styles["HeaderCell"] if row_index == 0 else styles["Cell"]
        converted.append([Paragraph(str(cell), style) for cell in row])
    t = Table(converted, colWidths=widths, repeatRows=1)
    t.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#1e3a8a")),
                ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
                ("GRID", (0, 0), (-1, -1), 0.35, colors.HexColor("#cbd5e1")),
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, colors.HexColor("#f8fafc")]),
                ("LEFTPADDING", (0, 0), (-1, -1), 5),
                ("RIGHTPADDING", (0, 0), (-1, -1), 5),
                ("TOPPADDING", (0, 0), (-1, -1), 5),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
            ]
        )
    )
    return t


def code_block(text, styles):
    return Preformatted(
        text,
        ParagraphStyle(
            "Code",
            fontName="Courier",
            fontSize=7.5,
            leading=10,
            textColor=colors.HexColor("#0f172a"),
            backColor=colors.HexColor("#f1f5f9"),
            borderColor=colors.HexColor("#cbd5e1"),
            borderWidth=0.5,
            borderPadding=7,
            spaceBefore=4,
            spaceAfter=8,
        ),
    )


def on_page(canvas, doc):
    canvas.saveState()
    page_width, page_height = A4
    canvas.setStrokeColor(colors.HexColor("#e5e7eb"))
    canvas.line(doc.leftMargin, page_height - 0.52 * inch, page_width - doc.rightMargin, page_height - 0.52 * inch)
    canvas.setFont("Helvetica", 7.5)
    canvas.setFillColor(colors.HexColor("#64748b"))
    canvas.drawString(doc.leftMargin, page_height - 0.38 * inch, "StudyRoom Connect ERP - Roles and Permissions Guide")
    canvas.drawRightString(page_width - doc.rightMargin, 0.38 * inch, f"Page {doc.page}")
    canvas.restoreState()


def section(title, body, styles, diagram=None, extra=None, page_break=True):
    story = [h(title, styles)]
    if diagram:
        story.extend([diagram, Spacer(1, 8)])
    for item in body:
        if isinstance(item, str):
            story.append(p(item, styles))
        elif isinstance(item, tuple) and item[0] == "sub":
            story.append(sub(item[1], styles))
        elif isinstance(item, tuple) and item[0] == "bullets":
            story.append(bullets(item[1], styles))
        elif isinstance(item, tuple) and item[0] == "code":
            story.append(code_block(item[1], styles))
        elif isinstance(item, tuple) and item[0] == "note":
            story.append(Paragraph(item[1], styles["Note"]))
        else:
            story.append(item)
    if extra:
        story.extend(extra)
    if page_break:
        story.append(PageBreak())
    return story


def build_pdf():
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    doc = SimpleDocTemplate(
        str(OUTPUT),
        pagesize=A4,
        rightMargin=0.58 * inch,
        leftMargin=0.58 * inch,
        topMargin=0.72 * inch,
        bottomMargin=0.62 * inch,
        title="StudyRoom Connect ERP - Roles, Permissions, Add-ons and Staff Assignment Guide",
        author="StudyRoom TechLab",
    )
    styles = build_styles()
    story = []

    story.extend(
        [
            Spacer(1, 1.2 * inch),
            Paragraph(
                "StudyRoom Connect ERP<br/>Roles, Permissions, Add-ons and Staff Assignment Guide",
                styles["CoverTitle"],
            ),
            Paragraph(
                "Implementation manual for role creation, add-on permission visibility, WiFiBilling permissions, and internal staff assignment.",
                styles["CoverSub"],
            ),
            FlowDiagram(
                ["Super Admin", "Company Admin", "Internal Staff", "ERP Add-ons", "WiFiBilling"],
                title="Platform actor chain",
            ),
            Spacer(1, 0.25 * inch),
            Paragraph(
                "Scope: Laravel ERP SaaS, Spatie Laravel Permission, Inertia React role pages, tenant-scoped staff users, and add-on-aware permission loading.",
                styles["Note"],
            ),
            Spacer(1, 1.1 * inch),
            Paragraph("Generated for C:/xampp/htdocs/billing-system", styles["CoverSub"]),
            PageBreak(),
        ]
    )

    toc = [
        ["Page", "Section"],
        ["3", "Executive Summary"],
        ["4", "Actor Model"],
        ["5", "Project Structure"],
        ["6", "MVC Request Flow"],
        ["7", "RoleController Permission Loading"],
        ["8", "Root Cause: Why WiFiBilling Was Hidden"],
        ["9", "Reusable Permission Loader"],
        ["10", "Role Create UI"],
        ["11", "Role Edit UI"],
        ["12", "Staff Assignment Model"],
        ["13", "User Creation Flow"],
        ["14", "User Edit Flow"],
        ["15", "Database and Model Relationships"],
        ["16", "Seeded Staff Profiles"],
        ["17", "Menu Visibility"],
        ["18", "Route Protection"],
        ["19", "Future Add-on Permission Steps"],
        ["20", "Troubleshooting"],
        ["21", "Cache Clearing"],
        ["22", "Verification Checklist"],
        ["23", "Affected Files"],
        ["24", "Implementation Sequence"],
    ]
    story.extend([h("Table of Contents", styles), table(toc, [0.7 * inch, 5.9 * inch], styles), PageBreak()])

    story.extend(
        section(
            "Executive Summary",
            [
                "The roles and internal staff assignment system now works for the full ERP SaaS platform, not only for WiFi Billing. Super Admin owns the platform, Company/Admin owns a tenant, and Internal Staff receive tenant-scoped roles.",
                "The role pages render grouped permissions from the backend. The fix adds one reusable loader for create and edit, normalizes permission metadata, supports enabled add-ons, and prevents stale or incomplete grouping from hiding valid WiFiBilling rows.",
                ("note", "Key rule: a permission must use the web guard, have usable add_on and module metadata, and pass the tenant add-on activation check before the role page can assign it."),
            ],
            styles,
            diagram=FlowDiagram(["Database rows", "Spatie permissions", "Backend grouping", "React role page"]),
        )
    )

    story.extend(
        section(
            "Actor Model",
            [
                "Super Admin is the platform owner and should not be mixed into tenant staff assignment. Company/Admin owns a tenant and creates roles for staff. Internal Staff users receive one or more tenant-scoped roles. Add-ons such as WiFiBilling contribute permissions to the tenant.",
                ("bullets", [
                    "Super Admin: controls plans, add-ons, company tenants, and platform settings.",
                    "Company/Admin: manages business users, roles, modules, billing, and operations.",
                    "Internal Staff: works inside a tenant with selected permissions.",
                    "WiFiBilling: an ERP add-on that adds ISP billing, package, router, provisioning, and voucher permissions.",
                ]),
            ],
            styles,
            diagram=FlowDiagram(["Super Admin", "Company/Admin", "Internal Staff", "Allowed Modules"], title="Access hierarchy"),
        )
    )

    story.extend(
        section(
            "Project Structure",
            [
                "The files below are the main project areas involved in roles, permissions, add-ons, and staff assignment.",
                ("code", """app/
  Http/Controllers/RoleController.php
  Http/Controllers/UserController.php
  Http/Requests/StoreUserRequest.php
  Http/Requests/UpdateUserRequest.php
  Models/User.php
  Models/AddOn.php
database/
  seeders/PermissionRoleSeeder.php
  seeders/IspRoleSeeder.php
  seeders/StaffRoleSeeder.php
resources/js/pages/
  roles/create.tsx
  roles/edit.tsx
  users/create.tsx
  users/edit.tsx
routes/
  web.php
packages/studyroomtechlab/WifiBilling/
  src/Http/Controllers/DashboardController.php
  src/Routes/web.php
  src/Resources/js/menus/company-menu.ts"""),
            ],
            styles,
            diagram=StackDiagram(
                [
                    ("Routes", "routes/web.php and add-on route files"),
                    ("Controllers", "RoleController and UserController"),
                    ("Requests", "StoreUserRequest and UpdateUserRequest"),
                    ("Models", "User, AddOn, Spatie Role, Spatie Permission"),
                    ("Views", "React pages under resources/js/pages"),
                ],
                title="MVC and add-on structure",
            ),
        )
    )

    story.extend(
        section(
            "MVC Request Flow",
            [
                "Laravel routes receive the browser request, controllers prepare tenant-scoped data, Inertia passes props to React, and React renders role or user forms. The role checkboxes are not hard-coded in the page; they are generated from the permission data sent by the controller.",
                ("bullets", [
                    "`GET /roles/create` maps to `RoleController@create`.",
                    "`GET /roles/{role}/edit` maps to `RoleController@edit`.",
                    "`GET /users` maps to `UserController@index`, which passes assignable roles to user modals.",
                    "`POST /users` maps to `UserController@store`, which creates the user and assigns the selected role.",
                ]),
            ],
            styles,
            diagram=FlowDiagram(["Route", "Controller", "Inertia props", "React page", "User action"], title="Request to UI pipeline"),
        )
    )

    story.extend(
        section(
            "RoleController Permission Loading",
            [
                "`RoleController@create` and `RoleController@edit` now call the same reusable assignable permission loader. The loader queries Spatie permissions directly, keeps only the web guard, filters add-ons by tenant activation, normalizes missing metadata, and groups the result by add-on then module.",
                "This avoids the old behavior where role pages depended only on `Auth::user()->getAllPermissions()`. A company/admin can now assign valid permissions for enabled add-ons even when the add-on key needs normalization.",
                ("code", """$permissions = Permission::query()
    ->where('guard_name', 'web')
    ->get()
    ->filter(fn ($permission) => $this->permissionIsAssignable($permission))
    ->map(fn ($permission) => [
        'id' => $permission->id,
        'name' => $permission->name,
        'label' => $permission->label ?: Str::headline($permission->name),
        'module' => $permission->module ?: 'General',
        'add_on' => $permission->add_on ?: 'Core',
        'guard_name' => $permission->guard_name,
    ])
    ->groupBy('add_on')
    ->map(fn ($items) => $items->groupBy('module'));"""),
            ],
            styles,
            diagram=FlowDiagram(["Permission rows", "active add-on filter", "normalize", "group module", "React props"], title="Implemented backend grouping"),
        )
    )

    story.extend(
        section(
            "Root Cause: Why WiFiBilling Was Hidden",
            [
                "The concrete mismatch was add-on naming. The package module key is `WifiBilling`, while existing permission rows used `WiFiBilling`. A strict `Module_is_active($addOn)` check can therefore reject valid rows even when the database permissions exist.",
                "The fix keeps reusable add-on logic by checking the normal module activation path first, then comparing activated module names case-insensitively. WiFi Billing remains one add-on; future add-ons can use the same loader.",
                ("bullets", [
                    "`guard_name` must be `web`.",
                    "`add_on` should match the registered module key, with a safe normalized fallback.",
                    "`module` should be a readable module group such as `mikrotik-routers` or `provisioning`.",
                    "The loader intentionally loads assignable permissions beyond the current user's direct permission list.",
                    "`Module_is_active('WiFiBilling')` must return true for the tenant.",
                    "Spatie permission cache may need reset after manual inserts.",
                ]),
            ],
            styles,
            diagram=StackDiagram(
                [
                    ("DB row exists", "Not enough by itself"),
                    ("Correct metadata", "add_on, module, label, guard_name"),
                    ("Tenant can assign", "company/admin owns or can see it"),
                    ("Module is active", "Plan/add-on activation passes"),
                    ("Cache is clear", "Spatie and Laravel caches reset"),
                ],
                title="Visibility requirements",
            ),
        )
    )

    story.extend(
        section(
            "Reusable Permission Loader",
            [
                "The reusable loader is in `RoleController`. Both create and edit pages use the same grouped permission shape: add_on -> module -> permissions.",
                ("bullets", [
                    "Normalize empty `add_on` to `Core`.",
                    "Normalize empty `module` to `General`.",
                    "Use `label` for display and `name` for submitted value.",
                    "Include core permissions and active add-on permissions.",
                    "Preserve add-on grouping so the UI can render tabs.",
                ]),
                ("code", """private function assignablePermissions()
{
    return Permission::query()
        ->where('guard_name', 'web')
        ->get()
        ->filter(fn ($permission) => $this->permissionIsAssignable($permission))
        ->map(fn ($permission) => [
            'id' => $permission->id,
            'name' => $permission->name,
            'label' => $permission->label ?: Str::headline($permission->name),
            'add_on' => $permission->add_on ?: 'Core',
            'module' => $permission->module ?: 'General',
        ])
        ->groupBy('add_on')
        ->map(fn ($items) => $items->groupBy('module'));
}"""),
            ],
            styles,
        )
    )

    story.extend(
        section(
            "Role Create UI",
            [
                "`resources/js/pages/roles/create.tsx` renders add-on tabs and module cards from the `permissions` prop. It now supports permission search plus select-all actions at all, add-on, and module levels.",
                ("bullets", [
                    "Search across add-on alias, module name, permission label, and permission name.",
                    "Select All applies to all currently visible permissions.",
                    "Select Add-on applies to the current tab.",
                    "Keep module checkbox indeterminate state.",
                    "Keep submitted values as permission names.",
                ]),
            ],
            styles,
            diagram=FlowDiagram(["Add-on tab", "Module card", "Permission checkbox", "Form submit"], title="Create role UI structure"),
        )
    )

    story.extend(
        section(
            "Role Edit UI",
            [
                "`resources/js/pages/roles/edit.tsx` mirrors create behavior and preloads `rolePermissions`. Because create and edit share the backend loader, WiFiBilling appears consistently in both pages.",
                ("bullets", [
                    "Use the same grouped permission prop shape.",
                    "Preserve selected permissions from `rolePermissions`.",
                    "Support selecting and clearing a module after existing data loads.",
                    "Respect non-editable role name rules while still allowing allowed permission edits where intended.",
                ]),
            ],
            styles,
            diagram=FlowDiagram(["Role", "Existing permissions", "Grouped permissions", "Edit form", "syncPermissions"], title="Edit role lifecycle"),
        )
    )

    story.extend(
        section(
            "Staff Assignment Model",
            [
                "Internal staff should be assigned a role/profile selected by the company/admin. The wording should be neutral ERP language: `Select a role/profile for this staff member. Permissions can be managed from Roles.`",
                "Do not create a second role system. This project already uses Spatie Laravel Permission through `HasRoles`, `assignRole`, and `syncRoles`.",
                ("bullets", [
                    "Do not show Super Admin role in tenant staff forms.",
                    "Load roles where `created_by = creatorId()`.",
                    "Validate that the selected role belongs to the same tenant.",
                    "Use `assignRole` on create and `syncRoles` on edit.",
                ]),
            ],
            styles,
            diagram=FlowDiagram(["Company/Admin", "Role dropdown", "UserController", "Spatie role", "Staff access"], title="Staff assignment flow"),
        )
    )

    story.extend(
        section(
            "User Creation Flow",
            [
                "`UserController@store` now reads the staff role/profile from `role_id` while keeping the older `type` fallback for compatibility. It validates tenant ownership, creates the user, and calls `$user->assignRole($role)`.",
                ("bullets", [
                    "Validate `role_id` and then verify it belongs to the current tenant.",
                    "Create the user with `created_by = creatorId()` and `creator_id = Auth::id()`.",
                    "Set staff type from selected role or a stable internal type such as `staff`.",
                    "Call `$user->assignRole($role)` after save.",
                ]),
            ],
            styles,
            diagram=StackDiagram(
                [
                    ("users/create.tsx", "selects role/profile"),
                    ("StoreUserRequest", "validates role_id"),
                    ("UserController@store", "creates tenant-scoped user"),
                    ("Spatie assignRole", "stores model_has_roles"),
                    ("Staff login", "receives permissions through role"),
                ],
                title="Create internal staff sequence",
            ),
        )
    )

    story.extend(
        section(
            "User Edit Flow",
            [
                "`resources/js/pages/users/edit.tsx` now includes the current staff role and provides the same Role/Profile dropdown. `UserController@update` uses `$user->syncRoles([$role])` when a valid tenant role is selected.",
                ("bullets", [
                    "Extend `EditUserFormData` with `role_id`.",
                    "Pass the current assigned role from `UserController@index`.",
                    "Validate the id format in `UpdateUserRequest` and tenant ownership in the controller.",
                    "Use `$user->syncRoles([$role])` for a single selected role.",
                    "Keep Super Admin roles hidden and blocked on the backend.",
                ]),
            ],
            styles,
            diagram=FlowDiagram(["Edit modal", "Selected role", "Update request", "syncRoles", "Updated access"], title="Edit staff role sequence"),
        )
    )

    story.extend(
        section(
            "Database and Model Relationships",
            [
                "The project uses Spatie Laravel Permission. Users get roles through `model_has_roles`, and roles get permissions through `role_has_permissions`. The `User` model includes the `HasRoles` trait.",
                ("bullets", [
                    "`users.created_by` scopes a user to a tenant.",
                    "`roles.created_by` scopes a role to a tenant.",
                    "`permissions.add_on` groups permissions by add-on.",
                    "`permissions.module` groups permissions inside each add-on.",
                    "`user_active_modules` and plan modules affect add-on availability.",
                ]),
            ],
            styles,
            diagram=StackDiagram(
                [
                    ("users", "tenant staff and admins"),
                    ("model_has_roles", "user to role pivot"),
                    ("roles", "tenant role/profile records"),
                    ("role_has_permissions", "role to permission pivot"),
                    ("permissions", "core and add-on permissions"),
                ],
                title="Permission model stack",
            ),
        )
    )

    story.extend(
        section(
            "Seeded Staff Profiles",
            [
                "Seed common tenant roles safely. Use `updateOrCreate` or `firstOrCreate` with `created_by` so re-running the seeder does not duplicate roles.",
                ("bullets", [
                    "Admin",
                    "Support Agent",
                    "Billing Officer",
                    "Sales / CRM",
                    "Network Technician",
                    "Installer",
                    "Marketing",
                    "Inventory Manager",
                    "Viewer",
                ]),
                ("code", """Role::updateOrCreate(
    ['name' => 'network-technician', 'guard_name' => 'web', 'created_by' => $companyId],
    ['label' => 'Network Technician', 'editable' => true]
);"""),
            ],
            styles,
        )
    )

    story.extend(
        section(
            "Menu Visibility",
            [
                "Menu visibility is controlled by both add-on activation and permission checks. Empty parent menu groups are removed after child filtering so staff do not see blank sections.",
                ("code", """if (Module_is_active('WiFiBilling') && Auth::user()->can('view-mikrotik-routers')) {
    // show MikroTik menu item
}"""),
                ("bullets", [
                    "Company/Admin with full WiFiBilling permissions sees the module.",
                    "Network Technician may see routers and provisioning only.",
                    "Billing Officer may see packages, customers, invoices, and vouchers.",
                    "Viewer may only see read-only dashboards.",
                ]),
            ],
            styles,
            diagram=FlowDiagram(["Module active?", "User can permission?", "Show menu item"], title="Menu decision"),
        )
    )

    story.extend(
        section(
            "Route Protection",
            [
                "Do not rely on React to protect data. WiFi Billing management controllers now reject unauthorized staff users while preserving tenant ownership checks.",
                ("code", """public function index()
{
    abort_unless(Auth::user()->can('view-mikrotik-routers'), 403);

    return MikrotikRouter::where('created_by', creatorId())->get();
}"""),
                ("bullets", [
                    "Provisioning list/detail checks use `view-provisioning-logs` or `manage-provisioning`.",
                    "Provisioning generate uses `create-provisioning-token` or `manage-provisioning`.",
                    "Provisioning deactivate uses `deactivate-provisioning-token` or `manage-provisioning`.",
                ]),
                ("note", "`/provision/{token}` remains public/token-based and was not moved into the authenticated permission checks."),
            ],
            styles,
            diagram=FlowDiagram(["HTTP request", "auth", "permission", "tenant scope", "response"], title="Route security"),
        )
    )

    story.extend(
        section(
            "Future Add-on Permission Steps",
            [
                "Every future add-on should provide permissions through a repeatable installation/seeding path. The role pages should not need hard-coded edits for each add-on.",
                ("bullets", [
                    "Create add-on permission seeder.",
                    "Set `guard_name = web`.",
                    "Set `add_on` to the exact module key.",
                    "Set `module` to the feature group.",
                    "Set a clear `label`.",
                    "Assign permissions to company/admin when the add-on is enabled.",
                    "Clear permission cache.",
                    "Confirm role create/edit render the new add-on tab.",
                ]),
            ],
            styles,
            diagram=FlowDiagram(["Seeder", "Permission rows", "Tenant assignment", "Cache reset", "Role UI"], title="Add-on lifecycle"),
        )
    )

    story.extend(
        section(
            "Troubleshooting",
            [
                "Use this checklist when permissions exist in the database but are not visible in the role page.",
                ("bullets", [
                    "Query the row and verify `name`, `guard_name`, `label`, `add_on`, and `module`.",
                    "Check whether the current company/admin has the permission.",
                    "Check `Module_is_active(add_on)` for the current user.",
                    "Check the add-on is installed and enabled.",
                    "Check the active plan or user active modules include the add-on.",
                    "Clear Laravel and Spatie permission caches.",
                    "Inspect Inertia props for `permissions` on `/roles/create`.",
                ]),
            ],
            styles,
            diagram=StackDiagram(
                [
                    ("DB row valid?", "guard, add_on, module, label"),
                    ("Tenant can see?", "company/admin has permission"),
                    ("Module active?", "Module_is_active returns true"),
                    ("Cache clear?", "optimize and permission reset"),
                    ("Props present?", "inspect roles/create permissions prop"),
                ],
                title="Permission visibility decision tree",
            ),
        )
    )

    story.extend(
        section(
            "Cache Clearing",
            [
                "After changing permission seeders, manual permission rows, role assignments, or add-on activation, clear caches before testing.",
                ("code", r"""C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan permission:cache-reset"""),
                "If `permission:cache-reset` is not available, continue after `optimize:clear` and `config:clear`. The command depends on the installed Spatie permission console commands.",
            ],
            styles,
            diagram=FlowDiagram(["Change permissions", "Reset Spatie cache", "Clear Laravel cache", "Reload UI"], title="Cache workflow"),
        )
    )

    story.extend(
        section(
            "Verification Checklist",
            [
                "Use this checklist after implementing the role and staff assignment fixes.",
                ("bullets", [
                    "`/roles/create` shows a WiFiBilling add-on group.",
                    "`/roles/edit` shows the same WiFiBilling group.",
                    "Network Technician role can be created.",
                    "MikroTik, packages, provisioning, and voucher permissions can be selected.",
                    "User creation shows Role/Profile, not fixed Support-only wording.",
                    "Internal staff can be assigned Network Technician.",
                    "Staff login only sees allowed menu items.",
                    "Unauthorized routes return forbidden or redirect safely.",
                    "Super Admin dashboard still works.",
                    "`/provision/{token}` still works.",
                    "PHP 8.2 syntax checks pass.",
                ]),
            ],
            styles,
            diagram=FlowDiagram(["Create role", "Assign staff", "Login as staff", "Check menus", "Check routes"], title="End-to-end verification"),
        )
    )

    affected = [
        ["File path", "Type", "Why it matters", "How to test"],
        ["app/Http/Controllers/RoleController.php", "Controller", "Loads and groups permissions for create/edit role pages.", "Open roles/create and inspect WiFiBilling groups."],
        ["resources/js/pages/roles/create.tsx", "View", "Renders add-on tabs, module boxes, and permission checkboxes.", "Search and select permissions."],
        ["resources/js/pages/roles/edit.tsx", "View", "Edits existing role permissions.", "Edit Network Technician."],
        ["app/Http/Controllers/UserController.php", "Controller", "Creates staff users and assigns selected Spatie roles.", "Create staff and confirm model_has_roles."],
        ["resources/js/pages/users/create.tsx", "View", "Displays staff Role/Profile dropdown.", "Create internal staff."],
        ["resources/js/pages/users/edit.tsx", "View", "Allows changing staff role.", "Edit staff and sync roles."],
        ["app/Http/Requests/StoreUserRequest.php", "Request", "Validates selected role on create.", "Submit invalid role id."],
        ["app/Http/Requests/UpdateUserRequest.php", "Request", "Should validate selected role on edit.", "Submit role from another tenant."],
        ["database/seeders/StaffRoleSeeder.php", "Seeder", "Seeds default tenant staff profiles safely.", "Rerun seeder in test database."],
        ["packages/studyroomtechlab/WifiBilling", "Add-on", "Contributes WiFi Billing routes, menus, and permissions.", "Enable add-on and open role form."],
    ]
    story.extend(
        section(
            "Affected Files",
            [
                "These files are the main places a developer should inspect or update for the roles, permissions, add-on visibility, and staff assignment work.",
                table(affected, [1.65 * inch, 0.7 * inch, 2.45 * inch, 1.8 * inch], styles),
            ],
            styles,
        )
    )

    story.extend(
        section(
            "Implementation Sequence",
            [
                "These are the implemented steps and the order to repeat for another add-on or tenant role expansion.",
                ("bullets", [
                    "Step 1: Add or repair reusable permission loader.",
                    "Step 2: Normalize permission metadata and add-on grouping.",
                    "Step 3: Update role create/edit UI search and select-all behavior.",
                    "Step 4: Seed staff profile roles for each company/admin safely.",
                    "Step 5: Update user create/edit forms to use Role/Profile.",
                    "Step 6: Validate role ownership on create and edit.",
                    "Step 7: Assign or sync Spatie roles in UserController.",
                    "Step 8: Clear caches and verify role pages.",
                    "Step 9: Login as staff and confirm menus/routes.",
                    "Step 10: Confirm Super Admin and provisioning route are untouched.",
                ]),
            ],
            styles,
            diagram=FlowDiagram(["Backend loader", "Role UI", "Staff roles", "User forms", "Testing"], title="Implemented order"),
            page_break=False,
        )
    )

    doc.build(story, onFirstPage=on_page, onLaterPages=on_page)
    return OUTPUT


if __name__ == "__main__":
    print(build_pdf())
