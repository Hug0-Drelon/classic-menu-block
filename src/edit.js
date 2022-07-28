/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { SelectControl, Placeholder, Disabled } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { navigation as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import useNavigationEntities from './use-navigation-entities';
import './editor.scss';
import metadata from './block';

const { name } = metadata;

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @param {Object}   props
 * @param {Function} props.setAttributes
 * @param {Object}   props.attributes
 * @param {boolean}  props.isSelected
 * @return {WPElement} Element to render.
 */
export default function Edit({ setAttributes, attributes, isSelected }) {
	const { menu = 0 } = attributes || {};

	const { language = 0 } = attributes || {};

	const { menus, hasMenus } = useNavigationEntities();

	const options = [{ value: 0, label: __('Not set', 'classic-menu-block') }];
	if (hasMenus) {
		menus.forEach(function (item) {
			options.push({ value: parseInt(item.id), label: item.name });
		});
	}

	const onSaveMenu = (value) => {
		setAttributes({ menu: parseInt(value) });
	};

	const onSaveLang = (value) => {
		setAttributes({ language: value });
	};

	const langOptions = [
		{ value: 'all', label: 'For All Languages' },
		{ value: 'en', label: 'English' },
		{ value: 'fr', label: 'Français' }
	];

	return (
		<div {...useBlockProps()}>
			{isSelected || !menu ? (
				<Placeholder
					label={__('Classic menu', 'classic-menu-block')}
					icon={icon}
					isColumnLayout='true'
				>
					<SelectControl
						label={__('Select a menu:', 'classic-menu-block')}
						options={options}
						value={menu}
						onChange={onSaveMenu}
					/>
					<SelectControl
						label={__('The menu is displayed for:', 'classic-menu-block')}
						options={langOptions}
						value={language}
						onChange={onSaveLang}
					/>
				</Placeholder>
			) : (
				<Disabled>
					<ServerSideRender block={name} attributes={attributes} />
				</Disabled>
			)}
		</div>
	);
}
