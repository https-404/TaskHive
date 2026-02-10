import { getApi } from './api.js';

/** Task status values matching backend (board column order). */
export const TASK_STATUSES = ['todo', 'in_progress', 'in_review', 'done'];

export const TASK_STATUS_LABELS = {
  todo: 'To Do',
  in_progress: 'In Progress',
  in_review: 'In Review',
  done: 'Done',
};

export const PRIORITY_LABELS = {
  low: 'Low',
  medium: 'Medium',
  high: 'High',
  urgent: 'Urgent',
};

export async function getProjects() {
  const api = getApi();
  if (!api) throw new Error('API not initialized');
  const { data } = await api.get('/api/projects');
  return data.projects;
}

export async function createProject(payload) {
  const api = getApi();
  if (!api) throw new Error('API not initialized');
  const { data } = await api.post('/api/projects', payload);
  return data.project;
}

export async function getTasks(projectId = null) {
  const api = getApi();
  if (!api) throw new Error('API not initialized');
  const params = projectId ? { project_id: projectId } : {};
  const { data } = await api.get('/api/tasks', { params });
  return data.tasks;
}

export async function createTask(payload) {
  const api = getApi();
  if (!api) throw new Error('API not initialized');
  const { data } = await api.post('/api/tasks', payload);
  return data.task;
}

export async function getUsers() {
  const api = getApi();
  if (!api) throw new Error('API not initialized');
  const { data } = await api.get('/api/users');
  return data.users;
}

export async function updateTask(id, payload) {
  const api = getApi();
  if (!api) throw new Error('API not initialized');
  const { data } = await api.patch(`/api/tasks/${id}`, payload);
  return data.task;
}

export async function updateTaskStatus(id, status) {
  return updateTask(id, { status });
}
