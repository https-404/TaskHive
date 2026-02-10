<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAuthStore } from '../stores/auth/index.js';
import {
  getProjects,
  getTasks,
  updateTaskStatus,
  updateTask,
  createProject,
  createTask,
  getUsers,
  TASK_STATUSES,
  TASK_STATUS_LABELS,
  PRIORITY_LABELS,
} from '../services/task.service.js';

const router = useRouter();
const authStore = useAuthStore();
const { user } = storeToRefs(authStore);

const projects = ref([]);
const tasks = ref([]);
const users = ref([]);
const selectedProjectId = ref(''); // '' = all projects
const loading = ref(true);
const error = ref('');
const draggingTaskId = ref(null);
const dropTargetStatus = ref(null);

// New project modal
const showProjectModal = ref(false);
const projectForm = ref({ name: '', description: '' });
const projectSubmitting = ref(false);
const projectError = ref('');

// New task modal
const showTaskModal = ref(false);
const taskForm = ref({
  title: '',
  description: '',
  project_id: '',
  status: 'todo',
  priority: 'medium',
  assigned_to: '',
  due_date: '',
  blocked: false,
});
const taskSubmitting = ref(false);
const taskError = ref('');

// Task detail modal (view task on card click)
const selectedTask = ref(null);
let justFinishedDrag = false;
const editingField = ref(null); // 'title' | 'description' | 'status' | 'priority' | 'assigned_to' | 'due_date' | 'blocked'
const editValue = ref('');
const detailSaving = ref(false);

const projectOptions = computed(() => [
  { id: null, name: 'All projects', tasks_count: null },
  ...projects.value,
]);

const columns = computed(() => {
  const statuses = [...TASK_STATUSES];
  const hasOther = tasks.value.some((t) => !TASK_STATUSES.includes(t.status));
  if (hasOther) statuses.push('other');
  return statuses.map((status) => ({
    status,
    label: status === 'other' ? 'Other' : TASK_STATUS_LABELS[status],
    tasks: tasks.value.filter((t) => (status === 'other' ? !TASK_STATUSES.includes(t.status) : t.status === status)),
  }));
});

async function loadProjects() {
  try {
    projects.value = await getProjects();
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Failed to load projects';
  }
}

async function loadTasks() {
  loading.value = true;
  error.value = '';
  try {
    tasks.value = await getTasks(selectedProjectId.value ? Number(selectedProjectId.value) : undefined);
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Failed to load tasks';
  } finally {
    loading.value = false;
  }
}

async function onProjectChange() {
  await loadTasks();
}

function startDrag(taskId) {
  draggingTaskId.value = taskId;
}

function endDrag() {
  draggingTaskId.value = null;
  dropTargetStatus.value = null;
  justFinishedDrag = true;
  setTimeout(() => {
    justFinishedDrag = false;
  }, 100);
}

function onColumnDragOver(status, e) {
  e.preventDefault();
  e.dataTransfer.dropEffect = 'move';
  dropTargetStatus.value = status;
}

function onColumnDragLeave() {
  dropTargetStatus.value = null;
}

async function onColumnDrop(targetStatus, e) {
  e.preventDefault();
  dropTargetStatus.value = null;
  const taskId = draggingTaskId.value;
  if (!taskId || targetStatus === 'other') return;
  const task = tasks.value.find((t) => t.id === taskId);
  if (!task || task.status === targetStatus) {
    endDrag();
    return;
  }
  // Optimistic update
  const prevStatus = task.status;
  task.status = targetStatus;
  try {
    await updateTaskStatus(taskId, targetStatus);
  } catch (err) {
    task.status = prevStatus;
    error.value = err.response?.data?.message || err.message || 'Failed to update task';
  }
  endDrag();
}

function priorityClass(priority) {
  const map = {
    low: 'bg-slate-100 text-slate-600',
    medium: 'bg-amber-100 text-amber-800',
    high: 'bg-orange-100 text-orange-800',
    urgent: 'bg-red-100 text-red-800',
  };
  return map[priority] || 'bg-slate-100 text-slate-600';
}

async function logout() {
  await authStore.logout();
  router.push('/auth');
}

function openProjectModal() {
  projectError.value = '';
  projectForm.value = { name: '', description: '' };
  showProjectModal.value = true;
}

function closeProjectModal() {
  showProjectModal.value = false;
}

async function submitProject() {
  projectError.value = '';
  if (!projectForm.value.name?.trim()) {
    projectError.value = 'Project name is required.';
    return;
  }
  projectSubmitting.value = true;
  try {
    await createProject({
      name: projectForm.value.name.trim(),
      description: projectForm.value.description?.trim() || null,
    });
    await loadProjects();
    closeProjectModal();
  } catch (e) {
    projectError.value = e.response?.data?.message || e.response?.data?.errors?.name?.[0] || e.message || 'Failed to create project';
  } finally {
    projectSubmitting.value = false;
  }
}

async function openTaskModal() {
  taskError.value = '';
  taskForm.value = {
    title: '',
    description: '',
    project_id: selectedProjectId.value || (projects.value[0]?.id ?? ''),
    status: 'todo',
    priority: 'medium',
    assigned_to: '',
    due_date: '',
    blocked: false,
  };
  if (users.value.length === 0) {
    try {
      users.value = await getUsers();
    } catch {
      // ignore
    }
  }
  showTaskModal.value = true;
}

function closeTaskModal() {
  showTaskModal.value = false;
}

function openTaskDetail(task) {
  if (justFinishedDrag) return;
  selectedTask.value = task;
  editingField.value = null;
  if (users.value.length === 0) {
    getUsers().then((u) => { users.value = u; });
  }
}

function closeTaskDetailModal() {
  selectedTask.value = null;
  editingField.value = null;
}

function startEdit(field) {
  if (!selectedTask.value) return;
  editingField.value = field;
  const t = selectedTask.value;
  if (field === 'title') editValue.value = t.title ?? '';
  else if (field === 'description') editValue.value = t.description ?? '';
  else if (field === 'status') editValue.value = t.status ?? 'todo';
  else if (field === 'priority') editValue.value = t.priority ?? 'medium';
  else if (field === 'assigned_to') editValue.value = t.assigned_to != null ? String(t.assigned_to) : '';
  else if (field === 'due_date') editValue.value = t.due_date ?? '';
  else if (field === 'blocked') editValue.value = t.blocked ?? false;
}

function cancelEdit() {
  editingField.value = null;
}

function getPayloadFromEdit() {
  const field = editingField.value;
  if (!field) return null;
  let value = editValue.value;
  if (field === 'assigned_to') value = value ? Number(value) : null;
  if (field === 'due_date' && value === '') value = null;
  return { [field]: value };
}

async function saveEdit() {
  if (!selectedTask.value || !editingField.value) return;
  const payload = getPayloadFromEdit();
  if (!payload) return;
  detailSaving.value = true;
  try {
    const updated = await updateTask(selectedTask.value.id, payload);
    Object.assign(selectedTask.value, updated);
    const inList = tasks.value.find((t) => t.id === updated.id);
    if (inList) Object.assign(inList, updated);
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Failed to update task';
  } finally {
    detailSaving.value = false;
    editingField.value = null;
  }
}

function formatDetailDate(iso) {
  if (!iso) return '—';
  try {
    const d = new Date(iso);
    return d.toLocaleDateString(undefined, { dateStyle: 'medium' }) + ' ' + d.toLocaleTimeString(undefined, { timeStyle: 'short' });
  } catch {
    return iso;
  }
}

async function submitTask() {
  taskError.value = '';
  if (!taskForm.value.title?.trim()) {
    taskError.value = 'Task title is required.';
    return;
  }
  const projectId = taskForm.value.project_id ? Number(taskForm.value.project_id) : null;
  if (!projectId) {
    taskError.value = 'Please select a project.';
    return;
  }
  taskSubmitting.value = true;
  try {
    await createTask({
      title: taskForm.value.title.trim(),
      description: taskForm.value.description?.trim() || null,
      project_id: projectId,
      status: taskForm.value.status,
      priority: taskForm.value.priority,
      assigned_to: taskForm.value.assigned_to ? Number(taskForm.value.assigned_to) : null,
      due_date: taskForm.value.due_date || null,
      blocked: taskForm.value.blocked,
    });
    await loadTasks();
    closeTaskModal();
  } catch (e) {
    const data = e.response?.data;
    taskError.value = data?.message || (data?.errors && Object.values(data.errors).flat()[0]) || e.message || 'Failed to create task';
  } finally {
    taskSubmitting.value = false;
  }
}

onMounted(async () => {
  await loadProjects();
  await loadTasks();
});
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <!-- Header -->
    <header class="sticky top-0 z-10 flex items-center justify-between gap-4 border-b border-slate-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur">
      <div class="flex items-center gap-6">
        <router-link to="/" class="flex items-center gap-2 font-semibold text-slate-800 no-underline">
          <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500 text-sm font-bold text-white">T</span>
          <span>TaskHive</span>
        </router-link>
        <div class="flex items-center gap-2">
          <label for="project-select" class="text-sm font-medium text-slate-600">Project</label>
          <select
            id="project-select"
            v-model="selectedProjectId"
            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
            @change="onProjectChange"
          >
            <option v-for="p in projectOptions" :key="p.id ?? 'all'" :value="p.id == null ? '' : p.id">
              {{ p.name }}{{ p.tasks_count != null ? ` (${p.tasks_count})` : '' }}
            </option>
          </select>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="rounded-lg bg-indigo-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            @click="openProjectModal"
          >
            New project
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            @click="openTaskModal"
          >
            New task
          </button>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-sm text-slate-600">{{ user?.name }}</span>
        <button
          type="button"
          class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
          @click="logout"
        >
          Log out
        </button>
      </div>
    </header>

    <main class="p-4">
      <p v-if="error" class="mb-4 rounded-lg bg-red-50 px-4 py-2 text-sm text-red-700">
        {{ error }}
      </p>

      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="h-8 w-8 animate-spin rounded-full border-2 border-indigo-500 border-t-transparent" />
      </div>

      <div v-else class="overflow-x-auto pb-4">
        <div class="flex min-w-max gap-4">
          <div
            v-for="col in columns"
            :key="col.status"
            class="column flex h-full w-72 flex-shrink-0 flex-col rounded-xl border-2 transition-colors"
            :class="
              dropTargetStatus === col.status && col.status !== 'other'
                ? 'border-indigo-400 bg-indigo-50/50'
                : 'border-slate-200 bg-slate-100/60'
            "
            @dragover="onColumnDragOver(col.status, $event)"
            @dragleave="onColumnDragLeave"
            @drop="onColumnDrop(col.status, $event)"
          >
            <div class="flex items-center justify-between px-4 py-3">
              <h2 class="font-semibold text-slate-800">
                {{ col.label }}
              </h2>
              <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-600">
                {{ col.tasks.length }}
              </span>
            </div>
            <div class="flex flex-1 flex-col gap-2 overflow-y-auto px-3 pb-3">
              <div
                v-for="task in col.tasks"
                :key="task.id"
                draggable="true"
                class="task-card cursor-grab rounded-lg border border-slate-200 bg-white p-3 shadow-sm transition-shadow active:cursor-grabbing hover:shadow-md"
                :class="{ 'opacity-60': draggingTaskId === task.id }"
                @click="openTaskDetail(task)"
                @dragstart="startDrag(task.id); $event.dataTransfer.setData('text/plain', task.id)"
                @dragend="endDrag"
              >
                <div class="flex items-start justify-between gap-2">
                  <span class="text-sm font-medium text-slate-900 line-clamp-2">{{ task.title }}</span>
                  <span
                    v-if="task.blocked"
                    class="flex-shrink-0 rounded bg-red-100 px-1.5 py-0.5 text-xs text-red-700"
                    title="Blocked"
                  >
                    Blocked
                  </span>
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                  <span
                    class="rounded px-1.5 py-0.5 text-xs font-medium"
                    :class="priorityClass(task.priority)"
                  >
                    {{ PRIORITY_LABELS[task.priority] || task.priority }}
                  </span>
                  <span v-if="task.assignee" class="text-xs text-slate-500">
                    {{ task.assignee.name }}
                  </span>
                  <span v-if="task.due_date" class="text-xs text-slate-500">
                    Due {{ task.due_date }}
                  </span>
                </div>
                <p v-if="task.project" class="mt-1 text-xs text-slate-400">
                  {{ task.project.name }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="!loading && tasks.length === 0" class="py-16 text-center text-slate-500">
        No tasks yet. Create a project and add tasks to see them here.
      </div>
    </main>

    <!-- New project modal -->
    <Teleport to="body">
      <div
        v-if="showProjectModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeProjectModal"
      >
        <div
          class="w-full max-w-md rounded-xl bg-white shadow-xl"
          role="dialog"
          aria-modal="true"
          aria-labelledby="project-modal-title"
        >
          <div class="border-b border-slate-200 px-6 py-4">
            <h2 id="project-modal-title" class="text-lg font-semibold text-slate-900">New project</h2>
          </div>
          <form class="p-6" @submit.prevent="submitProject">
            <p v-if="projectError" class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
              {{ projectError }}
            </p>
            <div class="space-y-4">
              <div>
                <label for="project-name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                <input
                  id="project-name"
                  v-model="projectForm.name"
                  type="text"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                  placeholder="Project name"
                  required
                />
              </div>
              <div>
                <label for="project-desc" class="mb-1 block text-sm font-medium text-slate-700">Description (optional)</label>
                <textarea
                  id="project-desc"
                  v-model="projectForm.description"
                  rows="3"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                  placeholder="Brief description"
                />
              </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
              <button
                type="button"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="closeProjectModal"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="projectSubmitting"
                class="rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-600 disabled:opacity-60"
              >
                {{ projectSubmitting ? 'Creating…' : 'Create project' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- New task modal -->
    <Teleport to="body">
      <div
        v-if="showTaskModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeTaskModal"
      >
        <div
          class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl bg-white shadow-xl"
          role="dialog"
          aria-modal="true"
          aria-labelledby="task-modal-title"
        >
          <div class="sticky top-0 border-b border-slate-200 bg-white px-6 py-4">
            <h2 id="task-modal-title" class="text-lg font-semibold text-slate-900">New task</h2>
          </div>
          <form class="p-6" @submit.prevent="submitTask">
            <p v-if="taskError" class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
              {{ taskError }}
            </p>
            <p v-if="projects.length === 0" class="mb-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
              Create a project first, then add tasks.
            </p>
            <div class="space-y-4">
              <div>
                <label for="task-title" class="mb-1 block text-sm font-medium text-slate-700">Title</label>
                <input
                  id="task-title"
                  v-model="taskForm.title"
                  type="text"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                  placeholder="Task title"
                  required
                />
              </div>
              <div>
                <label for="task-desc" class="mb-1 block text-sm font-medium text-slate-700">Description (optional)</label>
                <textarea
                  id="task-desc"
                  v-model="taskForm.description"
                  rows="2"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                  placeholder="Details"
                />
              </div>
              <div>
                <label for="task-project" class="mb-1 block text-sm font-medium text-slate-700">Project</label>
                <select
                  id="task-project"
                  v-model="taskForm.project_id"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                  required
                >
                  <option value="">Select project</option>
                  <option v-for="p in projects" :key="p.id" :value="p.id">
                    {{ p.name }}
                  </option>
                </select>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label for="task-status" class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                  <select
                    id="task-status"
                    v-model="taskForm.status"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                  >
                    <option v-for="s in TASK_STATUSES" :key="s" :value="s">
                      {{ TASK_STATUS_LABELS[s] }}
                    </option>
                  </select>
                </div>
                <div>
                  <label for="task-priority" class="mb-1 block text-sm font-medium text-slate-700">Priority</label>
                  <select
                    id="task-priority"
                    v-model="taskForm.priority"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                  >
                    <option v-for="(label, key) in PRIORITY_LABELS" :key="key" :value="key">
                      {{ label }}
                    </option>
                  </select>
                </div>
              </div>
              <div>
                <label for="task-assignee" class="mb-1 block text-sm font-medium text-slate-700">Assign to</label>
                <select
                  id="task-assignee"
                  v-model="taskForm.assigned_to"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                >
                  <option value="">Unassigned</option>
                  <option v-for="u in users" :key="u.id" :value="u.id">
                    {{ u.name }}
                  </option>
                </select>
              </div>
              <div>
                <label for="task-due" class="mb-1 block text-sm font-medium text-slate-700">Due date (optional)</label>
                <input
                  id="task-due"
                  v-model="taskForm.due_date"
                  type="date"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                />
              </div>
              <div class="flex items-center gap-2">
                <input
                  id="task-blocked"
                  v-model="taskForm.blocked"
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 text-indigo-500 focus:ring-indigo-500"
                />
                <label for="task-blocked" class="text-sm font-medium text-slate-700">Blocked</label>
              </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
              <button
                type="button"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="closeTaskModal"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="taskSubmitting || projects.length === 0"
                class="rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-600 disabled:opacity-60"
              >
                {{ taskSubmitting ? 'Creating…' : 'Create task' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Task detail modal (JIRA-style, double-click to edit) -->
    <Teleport to="body">
      <div
        v-if="selectedTask"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeTaskDetailModal"
      >
        <div
          class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-xl"
          role="dialog"
          aria-modal="true"
          aria-labelledby="task-detail-title"
        >
          <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-6 py-3">
            <span class="text-sm font-medium text-slate-500">Task</span>
            <button
              type="button"
              class="rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              aria-label="Close"
              @click="closeTaskDetailModal"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="p-6">
            <p v-if="detailSaving" class="mb-3 text-xs text-indigo-600">Saving…</p>
            <!-- JIRA-style two-column: label | value -->
            <div class="detail-grid">
              <!-- Task name -->
              <div class="detail-row">
                <span class="detail-label">Task name</span>
                <div
                  class="detail-value"
                  @dblclick="startEdit('title')"
                >
                  <input
                    v-if="editingField === 'title'"
                    v-model="editValue"
                    type="text"
                    class="detail-input"
                    @blur="saveEdit"
                    @keydown.enter="saveEdit"
                    @keydown.escape="cancelEdit"
                  >
                  <template v-else>
                    <span class="font-medium text-slate-900">{{ selectedTask.title || '—' }}</span>
                    <span class="detail-hint">Double-click to edit</span>
                  </template>
                </div>
              </div>
              <!-- Description -->
              <div class="detail-row">
                <span class="detail-label">Description</span>
                <div
                  class="detail-value detail-value-block"
                  @dblclick="startEdit('description')"
                >
                  <textarea
                    v-if="editingField === 'description'"
                    v-model="editValue"
                    rows="4"
                    class="detail-input w-full resize-y"
                    @blur="saveEdit"
                    @keydown.escape="cancelEdit"
                  />
                  <template v-else>
                    <span class="whitespace-pre-wrap text-slate-700">{{ selectedTask.description || 'No description' }}</span>
                    <span class="detail-hint">Double-click to edit</span>
                  </template>
                </div>
              </div>
              <!-- Status -->
              <div class="detail-row">
                <span class="detail-label">Status</span>
                <div
                  class="detail-value"
                  @dblclick="startEdit('status')"
                >
                  <select
                    v-if="editingField === 'status'"
                    v-model="editValue"
                    class="detail-input detail-select"
                    @change="saveEdit"
                    @blur="saveEdit"
                  >
                    <option v-for="s in TASK_STATUSES" :key="s" :value="s">{{ TASK_STATUS_LABELS[s] }}</option>
                  </select>
                  <template v-else>
                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-sm font-medium text-slate-700">
                      {{ TASK_STATUS_LABELS[selectedTask.status] || selectedTask.status }}
                    </span>
                    <span class="detail-hint">Double-click to edit</span>
                  </template>
                </div>
              </div>
              <!-- Priority -->
              <div class="detail-row">
                <span class="detail-label">Priority</span>
                <div
                  class="detail-value"
                  @dblclick="startEdit('priority')"
                >
                  <select
                    v-if="editingField === 'priority'"
                    v-model="editValue"
                    class="detail-input detail-select"
                    @change="saveEdit"
                    @blur="saveEdit"
                  >
                    <option v-for="(label, key) in PRIORITY_LABELS" :key="key" :value="key">{{ label }}</option>
                  </select>
                  <template v-else>
                    <span
                      class="inline-flex rounded px-2 py-0.5 text-sm font-medium"
                      :class="priorityClass(selectedTask.priority)"
                    >
                      {{ PRIORITY_LABELS[selectedTask.priority] || selectedTask.priority }}
                    </span>
                    <span class="detail-hint">Double-click to edit</span>
                  </template>
                </div>
              </div>
              <!-- Project (read-only) -->
              <div class="detail-row">
                <span class="detail-label">Project</span>
                <div class="detail-value">
                  <span class="text-slate-700">{{ selectedTask.project?.name ?? '—' }}</span>
                </div>
              </div>
              <!-- Assignee -->
              <div class="detail-row">
                <span class="detail-label">Assignee</span>
                <div
                  class="detail-value"
                  @dblclick="startEdit('assigned_to')"
                >
                  <select
                    v-if="editingField === 'assigned_to'"
                    v-model="editValue"
                    class="detail-input detail-select"
                    @change="saveEdit"
                    @blur="saveEdit"
                  >
                    <option value="">Unassigned</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                  </select>
                  <template v-else>
                    <span class="text-slate-700">{{ selectedTask.assignee?.name ?? 'Unassigned' }}</span>
                    <span class="detail-hint">Double-click to edit</span>
                  </template>
                </div>
              </div>
              <!-- Due date -->
              <div class="detail-row">
                <span class="detail-label">Due date</span>
                <div
                  class="detail-value"
                  @dblclick="startEdit('due_date')"
                >
                  <input
                    v-if="editingField === 'due_date'"
                    v-model="editValue"
                    type="date"
                    class="detail-input detail-select"
                    @change="saveEdit"
                    @blur="saveEdit"
                  >
                  <template v-else>
                    <span class="text-slate-700">{{ selectedTask.due_date ?? '—' }}</span>
                    <span class="detail-hint">Double-click to edit</span>
                  </template>
                </div>
              </div>
              <!-- Blocked -->
              <div class="detail-row">
                <span class="detail-label">Blocked</span>
                <div
                  class="detail-value"
                  @dblclick="startEdit('blocked')"
                >
                  <label v-if="editingField === 'blocked'" class="flex cursor-pointer items-center gap-2">
                    <input
                      v-model="editValue"
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 text-indigo-500"
                      @change="saveEdit"
                    >
                    <span class="text-sm">{{ editValue ? 'Yes' : 'No' }}</span>
                  </label>
                  <template v-else>
                    <span
                      v-if="selectedTask.blocked"
                      class="inline-flex rounded bg-red-100 px-2 py-0.5 text-sm font-medium text-red-700"
                    >
                      Yes
                    </span>
                    <span v-else class="text-slate-600">No</span>
                    <span class="detail-hint">Double-click to edit</span>
                  </template>
                </div>
              </div>
              <!-- Created (read-only) -->
              <div class="detail-row detail-row-muted">
                <span class="detail-label">Created</span>
                <div class="detail-value">
                  <span class="text-slate-500 text-sm">{{ formatDetailDate(selectedTask.created_at) }}</span>
                </div>
              </div>
              <!-- Updated (read-only) -->
              <div class="detail-row detail-row-muted">
                <span class="detail-label">Last updated</span>
                <div class="detail-value">
                  <span class="text-slate-500 text-sm">{{ formatDetailDate(selectedTask.updated_at) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.column {
  min-height: 420px;
}
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Task detail modal – JIRA-style two-column layout */
.detail-grid {
  display: flex;
  flex-direction: column;
  gap: 0;
}
.detail-row {
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 1rem;
  align-items: start;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--tw-slate-200, #e2e8f0);
}
.detail-row:last-child {
  border-bottom: none;
}
.detail-row-muted {
  padding-top: 0.5rem;
}
.detail-label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--tw-slate-500, #64748b);
  padding-top: 2px;
}
.detail-value {
  min-height: 2rem;
  cursor: text;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}
.detail-value-block {
  cursor: text;
  align-items: flex-start;
}
.detail-hint {
  font-size: 0.75rem;
  color: var(--tw-slate-400, #94a3b8);
}
.detail-input {
  width: 100%;
  max-width: 24rem;
  padding: 0.375rem 0.5rem;
  font-size: 0.875rem;
  border: 1px solid var(--tw-slate-300, #cbd5e1);
  border-radius: 0.375rem;
  outline: none;
}
.detail-input:focus {
  border-color: var(--tw-indigo-500, #6366f1);
  box-shadow: 0 0 0 2px rgb(99 102 241 / 0.2);
}
.detail-select {
  max-width: 14rem;
}
</style>
