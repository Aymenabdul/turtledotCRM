<?php
/**
 * Tool Selection Component
 * Renders a grid of tools with checkboxes for selection
 * 
 * @param array $availableTools List of all tools from config
 * @param array $selectedIds List of tool IDs currently selected
 */
function renderToolSelection($availableTools, $selectedIds = [])
{
    ?>
    <div class="tools-selection">
        <?php foreach ($availableTools as $key => $tool): ?>
            <label class="tool-option-label">
                <input type="checkbox" name="tools[]" value="<?php echo $key; ?>" class="tool-checkbox" <?php echo in_array($key, $selectedIds) ? 'checked' : ''; ?>>
                <div class="tool-option-card" style="--tool-color: <?php echo $tool['color'] ?? 'var(--primary)'; ?>">
                    <div class="tool-option-icon">
                        <i class="fa-solid <?php echo $tool['icon']; ?>"></i>
                    </div>
                    <div class="tool-option-info">
                        <span class="tool-option-name">
                            <?php echo $tool['name']; ?>
                        </span>
                        <span class="tool-option-desc">
                            <?php echo $tool['description'] ?? ''; ?>
                        </span>
                    </div>
                    <div class="tool-option-check">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
            </label>
        <?php endforeach; ?>
    </div>

    <style>
        .tools-selection {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .tool-option-label {
            cursor: pointer;
        }

        .tool-checkbox {
            display: none;
        }

        .tool-option-card {
            background: var(--bg-main);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.2s ease;
            position: relative;
            height: 100%;
        }

        .tool-option-icon {
            width: 40px;
            height: 40px;
            background: rgba(0, 0, 0, 0.03);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--tool-color);
            transition: all 0.2s ease;
        }

        .tool-option-info {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .tool-option-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-main);
        }

        .tool-option-desc {
            font-size: 0.75rem;
            color: var(--text-muted);
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .tool-option-check {
            position: absolute;
            top: -8px;
            right: -8px;
            color: var(--tool-color);
            font-size: 1.2rem;
            background: white;
            border-radius: 50%;
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Hover State */
        .tool-option-label:hover .tool-option-card {
            border-color: var(--tool-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Checked State */
        .tool-checkbox:checked+.tool-option-card {
            border-color: var(--tool-color);
            background: rgba(var(--tool-color), 0.02);
        }

        .tool-checkbox:checked+.tool-option-card .tool-option-icon {
            background: var(--tool-color);
            color: white;
        }

        .tool-checkbox:checked+.tool-option-card .tool-option-check {
            opacity: 1;
            transform: scale(1);
        }
    </style>
    <?php
}
