/**
 * WordPress dependencies
 */
import { store as blocksStore } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import warning from '@wordpress/warning';

/**
 * Internal dependencies
 */
import groups from './groups';
import { store as blockEditorStore } from '../../store';
import { useBlockEditContext } from '../block-edit/context';
import useDisplayBlockControls from '../use-display-block-controls';

export default function useInspectorControlsFill( group, exposeToChildren ) {
	const isDisplayed = useDisplayBlockControls();
	const { clientId } = useBlockEditContext();
	const isParentDisplayed = useSelect(
		( select ) => {
			const { getBlockName, hasSelectedInnerBlock } = select(
				blockEditorStore
			);
			const { hasBlockSupport } = select( blocksStore );
			return (
				exposeToChildren &&
				hasBlockSupport(
					getBlockName( clientId ),
					'__experimentalExposeControlsToChildren',
					false
				) &&
				hasSelectedInnerBlock( clientId )
			);
		},
		[ exposeToChildren, clientId ]
	);

	if ( ! groups[ group ] ) {
		warning( `Unknown InspectorControls group "${ group }" provided.` );
		return null;
	}
	if ( isDisplayed ) {
		return groups[ group ].Fill;
	}
	if ( isParentDisplayed ) {
		return groups.parent.Fill;
	}
	return null;
}
