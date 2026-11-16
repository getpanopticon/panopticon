# Task callbacks

In `src/Task/*` callbacks (`__invoke(object $task, Registry $storage)`), `$task` is a **plain `stdClass`**, built by `(object) $pendingTask->toArray()` in `Model\Task::runNextTask()` (`src/Model/Task.php:579`). It is NEVER an `Akeeba\Panopticon\Model\Task` instance.

Consequences (both were real bugs — GitHub #1034, fixed 2026-07):
- `if ($task instanceof \...\Model\Task)` in a callback is **always false** (dead code). To delete a completed one-shot task, set `run_once => 'delete'` in its params so the runner deletes the real model in its `finally` block; don't call `$task->delete()` in the callback.
- To persist a param change from a callback, load a fresh model: `$this->container->mvcFactory->makeTempModel('Task')->findOrFail($task->id)->save([...])`. BUT the runner's bookkeeping `save()` (AWF `DataModel::save` rewrites the whole row) would clobber it with its stale in-memory copy — so `runNextTask()` re-reads the `params` column from the DB right before that save to preserve callback-persisted param changes.

Related: `list-view-conventions.md`.
