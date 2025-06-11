/**
 * Mobility Trailblazers - Assignment Matrix View
 * File: assets/js/assignment-matrix-view.js
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    class AssignmentMatrixView {
        constructor(candidates, juryMembers, assignmentData, currentStage) {
            this.candidates = candidates;
            this.juryMembers = juryMembers;
            this.assignmentData = assignmentData;
            this.currentStage = currentStage;
            this.selectedCells = new Set();
            this.isDragging = false;
            this.dragStartCell = null;
            
            // Configuration
            this.config = {
                cellSize: 40,
                headerHeight: 60,
                headerWidth: 200,
                minCellSize: 30,
                maxCellSize: 60,
                zoomStep: 5
            };
        }

        /**
         * Render the matrix view
         */
        render() {
            return `
                <div class="mt-matrix-view">
                    <div class="mt-matrix-controls">
                        <div class="mt-matrix-zoom">
                            <button class="mt-zoom-out" title="Zoom Out">-</button>
                            <span class="mt-zoom-level">100%</span>
                            <button class="mt-zoom-in" title="Zoom In">+</button>
                        </div>
                        <div class="mt-matrix-actions">
                            <button class="mt-matrix-action" data-action="select-all">Select All</button>
                            <button class="mt-matrix-action" data-action="clear-selection">Clear Selection</button>
                            <button class="mt-matrix-action" data-action="assign-selected">Assign Selected</button>
                        </div>
                    </div>
                    <div class="mt-matrix-container">
                        <div class="mt-matrix-scroll">
                            <table class="mt-matrix-table">
                                <thead>
                                    <tr>
                                        <th class="mt-matrix-corner"></th>
                                        ${this.renderJuryHeaders()}
                                    </tr>
                                </thead>
                                <tbody>
                                    ${this.renderMatrixRows()}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }

        /**
         * Render jury member headers
         */
        renderJuryHeaders() {
            return this.juryMembers.map(jury => `
                <th class="mt-jury-header" data-jury-id="${jury.id}">
                    <div class="mt-jury-header-content">
                        <div class="mt-jury-name">${this.escapeHtml(jury.display_name)}</div>
                        <div class="mt-jury-stats">
                            <span class="mt-assigned">${jury.workload_analysis?.total_assigned || 0}</span>
                            <span class="mt-voted">${jury.workload_analysis?.total_voted || 0}</span>
                        </div>
                    </div>
                </th>
            `).join('');
        }

        /**
         * Render matrix rows
         */
        renderMatrixRows() {
            return this.candidates.map(candidate => `
                <tr class="mt-candidate-row" data-candidate-id="${candidate.id}">
                    <td class="mt-candidate-header">
                        <div class="mt-candidate-header-content">
                            <div class="mt-candidate-name">${this.escapeHtml(candidate.name)}</div>
                            <div class="mt-candidate-company">${this.escapeHtml(candidate.company)}</div>
                        </div>
                    </td>
                    ${this.renderCandidateCells(candidate)}
                </tr>
            `).join('');
        }

        /**
         * Render cells for a candidate
         */
        renderCandidateCells(candidate) {
            return this.juryMembers.map(jury => {
                const isAssigned = this.isAssigned(candidate.id, jury.id);
                const cellClass = `mt-matrix-cell ${isAssigned ? 'assigned' : ''}`;
                
                return `
                    <td class="${cellClass}" 
                        data-candidate-id="${candidate.id}" 
                        data-jury-id="${jury.id}">
                        <div class="mt-cell-content">
                            ${isAssigned ? '✓' : ''}
                        </div>
                    </td>
                `;
            }).join('');
        }

        /**
         * Initialize event handlers for the matrix view
         */
        initializeEventHandlers() {
            // Cell selection
            $(document).on('mousedown', '.mt-matrix-cell', (e) => {
                this.handleCellMouseDown(e);
            });

            $(document).on('mouseover', '.mt-matrix-cell', (e) => {
                this.handleCellMouseOver(e);
            });

            $(document).on('mouseup', () => {
                this.handleCellMouseUp();
            });

            // Zoom controls
            $('.mt-zoom-in').on('click', () => this.handleZoom(1));
            $('.mt-zoom-out').on('click', () => this.handleZoom(-1));

            // Matrix actions
            $('.mt-matrix-action').on('click', (e) => {
                const action = $(e.target).data('action');
                this.handleMatrixAction(action);
            });

            // Prevent text selection during drag
            $('.mt-matrix-table').on('selectstart', false);
        }

        /**
         * Handle cell mouse down event
         */
        handleCellMouseDown(e) {
            this.isDragging = true;
            this.dragStartCell = $(e.target).closest('.mt-matrix-cell');
            this.toggleCellSelection(this.dragStartCell);
        }

        /**
         * Handle cell mouse over event
         */
        handleCellMouseOver(e) {
            if (!this.isDragging) return;
            
            const currentCell = $(e.target).closest('.mt-matrix-cell');
            if (currentCell.length) {
                this.toggleCellSelection(currentCell);
            }
        }

        /**
         * Handle cell mouse up event
         */
        handleCellMouseUp() {
            this.isDragging = false;
            this.dragStartCell = null;
        }

        /**
         * Toggle cell selection
         */
        toggleCellSelection($cell) {
            const cellId = `${$cell.data('candidate-id')}-${$cell.data('jury-id')}`;
            
            if (this.selectedCells.has(cellId)) {
                this.selectedCells.delete(cellId);
                $cell.removeClass('selected');
            } else {
                this.selectedCells.add(cellId);
                $cell.addClass('selected');
            }
        }

        /**
         * Handle zoom controls
         */
        handleZoom(direction) {
            const newSize = this.config.cellSize + (direction * this.config.zoomStep);
            
            if (newSize >= this.config.minCellSize && newSize <= this.config.maxCellSize) {
                this.config.cellSize = newSize;
                this.updateZoomLevel();
                this.updateMatrixLayout();
            }
        }

        /**
         * Update zoom level display
         */
        updateZoomLevel() {
            const zoomPercentage = Math.round((this.config.cellSize / this.config.minCellSize) * 100);
            $('.mt-zoom-level').text(`${zoomPercentage}%`);
        }

        /**
         * Update matrix layout after zoom
         */
        updateMatrixLayout() {
            $('.mt-matrix-cell').css({
                width: `${this.config.cellSize}px`,
                height: `${this.config.cellSize}px`
            });
        }

        /**
         * Handle matrix actions
         */
        handleMatrixAction(action) {
            switch (action) {
                case 'select-all':
                    this.selectAllCells();
                    break;
                case 'clear-selection':
                    this.clearSelection();
                    break;
                case 'assign-selected':
                    this.assignSelectedCells();
                    break;
            }
        }

        /**
         * Select all cells
         */
        selectAllCells() {
            $('.mt-matrix-cell').addClass('selected');
            this.selectedCells = new Set(
                Array.from($('.mt-matrix-cell')).map(cell => 
                    `${$(cell).data('candidate-id')}-${$(cell).data('jury-id')}`
                )
            );
        }

        /**
         * Clear selection
         */
        clearSelection() {
            $('.mt-matrix-cell').removeClass('selected');
            this.selectedCells.clear();
        }

        /**
         * Assign selected cells
         */
        assignSelectedCells() {
            const assignments = Array.from(this.selectedCells).map(cellId => {
                const [candidateId, juryId] = cellId.split('-');
                return { candidateId, juryId };
            });

            // Trigger assignment event
            $(document).trigger('mt:assignments:bulk', [assignments]);
        }

        /**
         * Check if a candidate is assigned to a jury member
         */
        isAssigned(candidateId, juryId) {
            return this.assignmentData.assignments?.some(
                assignment => assignment.candidate_id === candidateId && assignment.jury_id === juryId
            );
        }

        /**
         * Utility method to escape HTML
         */
        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    // Make AssignmentMatrixView available globally
    window.AssignmentMatrixView = AssignmentMatrixView;

})(jQuery); 